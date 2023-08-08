<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Manager;

use AlexGeno\PhoneVerification\Exception\Otp;
use AlexGeno\PhoneVerification\Exception\RateLimit;
use AlexGeno\PhoneVerification\Manager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test the Manager with a custom config
 */
final class CustomConfigTest extends BaseTest
{
    private const MAX_ATTEMPTS_TO_COMPLETE = 5;
    private const PERIOD_SECS_TO_COMPLETE = 200;

    private const MAX_ATTEMPTS_TO_INITIATE = 10;
    private const PERIOD_SECS_TO_INITIATE = 3600;

    /**
     * Otp lengths data provider
     * @return array<mixed>
     */
    public function otpLengths(): array
    {
        return [
            // Values: phone, otp length, otp min, otp max
            '2_digits_otp' => ['+15417543010', 2, 10, 99],
            '3_digits_otp' => ['+15417543010',3, 100, 999],
            '4_digits_otp' => ['+15417543010',4, 1000, 9999],
            '5_digits_otp' => ['+15417543010',5, 10000, 99999],
            '6_digits_otp' => ['+15417543010',6, 100000, 999999],
        ];
    }

    /**
     * Checks if the verification process goes as expected
     * making the maximum possible attempts to complete with an incorrect OTP
     * and then trying a correct one
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testMaxAttemptsToCompleteExceeded(string $phone): void
    {
        $customIncorrectMessage = 'CODE IS SUPER INCORRECT';
        $config = [
            'otp' => ['message_incorrect' => fn($otp) => $customIncorrectMessage],
            'rate_limits' => [
                'complete' => ['period_secs' => self::PERIOD_SECS_TO_COMPLETE, 'count' => self::MAX_ATTEMPTS_TO_COMPLETE]
            ]
        ];
        $manager = new Manager($this->storageMock, $config);
        $manager->sender($this->senderMock)->initiate($phone);
        $otp = $manager->otp();
        $incorrectOtp = $otp + 1;
        $this->assertGreaterThan(0, $otp);

        // Max attempts with a wrong otp
        for ($i = 0; $i < self::MAX_ATTEMPTS_TO_COMPLETE; ++$i) {
            // Impossible to use expectException because it immediately takes us out of a test method
            try {
                // Repeatedly complete with an incorrect otp
                $manager->complete($phone, $incorrectOtp);
                $this->fail('Otp has not been thrown');
            } catch (Otp $e) {
                $this->assertEquals(Otp::CODE_INCORRECT, $e->getCode());
                $this->assertEquals($customIncorrectMessage, $e->getMessage());
            }
        }

        // Max attempts exceeded: a correct otp doesn't work anymore
        try {
            $manager->complete($phone, $otp);
            $this->fail('RateLimit has not been not thrown');
        } catch (RateLimit $e) {
            $this->assertEquals(RateLimit::CODE_COMPLETE, $e->getCode());
        }
    }

    /**
     * Checks if the verification process goes as expected
     * making (the maximum possible attempts - 1) to complete with an incorrect OTP
     * and then trying a correct one
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testMaxAttemptsToCompleteNotExceeded(string $phone): void
    {
        $config = [
            'rate_limits' => [
                'complete' => ['period_secs' => self::PERIOD_SECS_TO_COMPLETE, 'count' => self::MAX_ATTEMPTS_TO_COMPLETE]
            ]
        ];
        $manager = new Manager($this->storageMock, $config);

        $manager->sender($this->senderMock)->initiate($phone);
        $otp = $manager->otp();
        $incorrectOtp = $otp - 1;
        $this->assertGreaterThan(0, $otp);

        // Max attempts - 1 with a wrong otp
        for ($i = 0; $i < self::MAX_ATTEMPTS_TO_COMPLETE - 1; ++$i) {
            // Impossible to use expectException because it immediately takes it out of a test method
            try {
                // Repeatedly complete with an incorrect otp
                $manager->complete($phone, $incorrectOtp);
                $this->fail('Otp has not been thrown');
            } catch (Otp $e) {
                $this->assertEquals(Otp::CODE_INCORRECT, $e->getCode());
                $this->assertEquals('Code is incorrect', $e->getMessage());
            }
        }

        // Last chance: a correct otp still works
        $self = $manager->complete($phone, $otp);
        $this->assertEquals($manager, $self);
    }

    /**
     * Checks if the initiation stops being available when there are too many of them
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testMaxAttemptsToInitiateExceeded(string $phone): void
    {
        $customMessage = 'Too many attempts to initiate';
        $config = [
            'rate_limits' => [
                'initiate' => ['period_secs' => self::PERIOD_SECS_TO_INITIATE,
                 'count' => self::MAX_ATTEMPTS_TO_INITIATE,
                 'message' => fn() => $customMessage
                ]
            ]
        ];

        $manager = (new Manager($this->storageMock, $config))->sender($this->senderMock);

        // Exceeding all available initiations
        for ($i = 0; $i < self::MAX_ATTEMPTS_TO_INITIATE; ++$i) {
            $manager->initiate($phone);
            $otp = $manager->otp();
            $this->assertGreaterThan(0, $otp);
            $this->assertGreaterThan(0, $otp);
        }

        try {
            $manager->initiate($phone);
            $this->fail('RateLimit has not been not thrown');
        } catch (RateLimit $e) {
            $this->assertEquals(RateLimit::CODE_INITIATE, $e->getCode());
            $this->assertEquals($customMessage, $e->getMessage());
        }
    }

    /**
     * Checks if there is an error when an incorrect config param has been used
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testOtpIncorrectConfig(string $phone): void
    {
        $this->expectException(\Error::class);
        (new Manager($this->storageMock, ['otp' => ['message' => '']]))->sender($this->senderMock)->initiate($phone);
    }


    /**
     * Checks if a generated otp has a correct length
     *
     * @dataProvider otpLengths
     * @param string  $phone
     * @param integer $otpLength
     * @param integer $min
     * @param integer $max
     * @return void
     */
    public function testOtpCustomLength(string $phone, int $otpLength, int $min, int $max): void
    {
        $manager = new Manager($this->storageMock, ['otp' => ['length' => $otpLength]]);

        $manager->sender($this->senderMock)->initiate($phone);
        $this->assertLessThanOrEqual($max, $manager->otp());
        $this->assertGreaterThanOrEqual($min, $manager->otp());
    }

    /**
     * Checks if a custom message goes to Sender
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testOtpCustomMessage(string $phone): void
    {
        $message = "Just a test message";
        $manager = new Manager($this->storageMock, ['otp' => ['message' => fn() => $message]]);

        $this->senderMock->expects($this->once())
            ->method('invoke')
            ->with($this->identicalTo($phone), $this->identicalTo($message));

        $response = $manager->sender($this->senderMock)->initiate($phone);
        $this->assertInstanceOf(Manager::class, $response);
    }
}
