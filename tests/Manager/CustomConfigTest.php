<?php

declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Manager;

use AlexGeno\PhoneVerification\Manager;
use phpmock\phpunit\PHPMock;

final class CustomConfigTest extends BaseTest
{
    use PHPMock;

    const MAX_ATTEMPTS_TO_COMPLETE = 5;
    const PERIOD_SECS_TO_COMPLETE = 200;

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
     * @runInSeparateProcess
     */
    public function testMaxAttemptsExceeded($phone): void
    {
        $config = [
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
                $this->fail('Otp was not thrown');
            } catch (\AlexGeno\PhoneVerification\Exception\Otp $e) {
                $this->assertEquals($incorrectOtp, $e->otp());
                $this->assertEquals($phone, $e->phone());
            }
        }

        //correct otp doesn't work anymore
        try {
            $manager->complete($phone, $otp); //correct otp
            $this->fail('RateLimit was not thrown');
        } catch (\AlexGeno\PhoneVerification\Exception\RateLimit $e) {
            $limits = $e->limits();
            $this->assertEquals(self::MAX_ATTEMPTS_TO_COMPLETE, $limits['count']);
            $this->assertEquals(self::PERIOD_SECS_TO_COMPLETE, $limits['period_secs']);
        }
    }

    /**
     * @dataProvider phoneNumbers
     * @runInSeparateProcess
     */
    public function testMaxAttemptsNotExceeded($phone): void
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
                $manager->complete($phone, $incorrectOtp); //wrong otp
                $this->fail('Otp was not thrown');
            } catch (\AlexGeno\PhoneVerification\Exception\Otp $e) {
                $this->assertEquals($incorrectOtp, $e->otp());
                $this->assertEquals($phone, $e->phone());
            }
        }

        //correct otp still works
        $self = $manager->complete($phone, $otp);
        $this->assertEquals($manager, $self);
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
     * @runInSeparateProcess
     */
    public function testOtpCustomMessage($phone): void
    {
        $message = "Just a test message";

        //Message is just otp code and nothing more
        $manager = new Manager($this->storageMock, $this->senderMock,['otp' => ['message' => fn() => $message]]);

        $this->senderMock->expects($this->once())->method('invoke')->with($this->identicalTo($phone), $message);

        $otp = $manager->start($phone);
        $this->assertGreaterThan(0, $otp);

    }

}
