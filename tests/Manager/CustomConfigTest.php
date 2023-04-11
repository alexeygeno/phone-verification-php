<?php

declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Manager;

use AlexGeno\PhoneVerification\Exception\Otp;
use AlexGeno\PhoneVerification\Exception\RateLimit;
use AlexGeno\PhoneVerification\Manager;
use phpmock\phpunit\PHPMock;

/**
 * Class CustomConfigTest
 * @package AlexGeno\PhoneVerificationTests\Manager
 */
final class CustomConfigTest extends BaseTest
{
    use PHPMock;

    const MAX_ATTEMPTS_TO_COMPLETE = 5;
    const PERIOD_SECS_TO_COMPLETE = 200;

    const MAX_ATTEMPTS_TO_INITIATE = 10;
    const PERIOD_SECS_TO_INITIATE = 3600;

    public function otpLengths(): array
    {
        return [
            '2_digits_code' => ['+380935258272', 2, 10, 99, 33], //phone, code length, code min, code max, code any
            '3_digits_code' => ['+380935258272',3, 100, 999, 444],
            '4_digits_code' => ['+380935258272',4, 1000, 9999, 5555],
            '5_digits_code' => ['+380935258272',5, 10000, 99999, 55555],
            '5_digits_code' => ['+380935258272',6, 100000, 999999, 666666],
        ];
    }

     /**
     * @dataProvider phoneNumbers
     */
    public function testMaxAttemptsToCompleteExceeded($phone): void
    {
        $customIncorrectMessage = 'CODE IS SUPER INCORRECT';
        $config = [
            'otp' => ['message_incorrect' => fn($otp) => $customIncorrectMessage],
            'rate_limits' => [
                'complete' => ['period_secs' => self::PERIOD_SECS_TO_COMPLETE, 'count' => self::MAX_ATTEMPTS_TO_COMPLETE]
            ]
        ];
        $manager = new Manager($this->storageMock, $this->senderMock, $config);
        $otp = $manager->start($phone);
        $this->assertGreaterThan(0, $otp);

        //Max attempts+1 with wrong otp
        for ($i = 0; $i < self::MAX_ATTEMPTS_TO_COMPLETE + 1; ++$i) {
            //impossible to use expectException because it immediately takes us out of a test method
            try {
                $incorrectOtp = $otp + 1;
                $manager->complete($phone, $incorrectOtp);
                $this->fail('Otp has not been thrown');
            } catch (Otp $e) {
                $this->assertEquals(Otp::CODE_INCORRECT, $e->getCode());
                $this->assertEquals($customIncorrectMessage, $e->getMessage());
            }
        }

        //correct otp doesn't work anymore
        try {
            $manager->complete($phone, $otp); //correct otp
            $this->fail('RateLimit has not been not thrown');
        } catch (RateLimit $e) {
            $this->assertEquals(RateLimit::CODE_COMPLETE, $e->getCode());
        }
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testMaxAttemptsToCompleteNotExceeded($phone): void
    {
        $config = [
            'rate_limits' => [
                'complete' => ['period_secs' => self::PERIOD_SECS_TO_COMPLETE, 'count' => self::MAX_ATTEMPTS_TO_COMPLETE]  //you can complete confirmation 6 times per 6 minutes
            ]
        ];
        $manager = new Manager($this->storageMock, $this->senderMock, $config);
        $otp = $manager->start($phone);
        $this->assertGreaterThan(0, $otp);

        //Max attempts with wrong otp
        for ($i = 0; $i < self::MAX_ATTEMPTS_TO_COMPLETE; ++$i) {
            //impossible to use expectException because it immediately takes it out of a test method
            try {
                $incorrectOtp = $otp - 1;
                $manager->complete($phone, $incorrectOtp); //incorrect otp
                $this->fail('Otp has not been thrown');
            } catch (Otp $e) {
                $this->assertEquals(Otp::CODE_INCORRECT, $e->getCode());
                $this->assertEquals('Code is incorrect', $e->getMessage());
            }
        }

        //correct otp still works
        $self = $manager->complete($phone, $otp);
        $this->assertEquals($manager, $self);
    }


    /**
     * @dataProvider phoneNumbers
     */
    public function testMaxAttemptsToInitiateExceeded($phone): void
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

        $manager = new Manager($this->storageMock, $this->senderMock, $config);

        //exceeding all available initiations
        for ($i = 0; $i < self::MAX_ATTEMPTS_TO_INITIATE; ++$i) {
            $otp = $manager->start($phone);
            $this->assertGreaterThan(0,$otp);
        }

        try {
            $manager->start($phone);
            $this->fail('RateLimit has not been not thrown');
        } catch (RateLimit $e) {
            $this->assertEquals(RateLimit::CODE_INITIATE, $e->getCode());
            $this->assertEquals($customMessage, $e->getMessage());
        }
    }



    /**
     */
    public function testOtpIncorrectConfig(): void
    {
        $this->expectException(\AlexGeno\PhoneVerification\Exception::class);
        new Manager($this->storageMock, $this->senderMock, ['otp' => ['message' => '']]);
    }

    /**
     * @dataProvider otpLengths
     * @runInSeparateProcess
     */
    public function testOtpCustomLength($phone, $otpLength, $min, $max, $any): void
    {
        $manager = new Manager($this->storageMock, $this->senderMock, ['otp' => ['length' => $otpLength]]);

        $rand = $this->getFunctionMock('AlexGeno\PhoneVerification', "rand");
        $rand->expects($this->once())->with($min, $max)->willReturn($any);

        $this->assertEquals($any, $manager->start($phone));
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testOtpCustomMessage($phone): void
    {
        $message = "Just a test message";

        $manager = new Manager($this->storageMock, $this->senderMock,['otp' => ['message' => fn() => $message]]);

        $this->senderMock->expects($this->once())->method('invoke')->with($this->identicalTo($phone), $message);

        $otp = $manager->start($phone);
        $this->assertGreaterThan(0, $otp);
    }

}
