<?php declare(strict_types=1);
namespace AlexGeno\PhoneVerificationTests\Manager;

use AlexGeno\PhoneVerification\Exception\MaxAttemptsExceeded;
use AlexGeno\PhoneVerification\Manager;
use AlexGeno\PhoneVerification\Storage\Redis;
use M6Web\Component\RedisMock\RedisMockFactory;
use phpmock\phpunit\PHPMock;
use Predis\Client;


final class ConfigTest extends BaseTest
{

    use PHPMock;

    const MAX_ATTEMPTS = 5;
    const OTP_EXP_PERIOD = 200;

    public function codeLengths(): array
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
    public function testMaxAttemptsExceeded($phone):void
    {
        $manager = new Manager($this->storageMock,  $this->providerMock, ['max_attempts' => self::MAX_ATTEMPTS, 'otp_exp_period' => self::OTP_EXP_PERIOD]);
        $otp = $manager->start($phone);
        $this->assertGreaterThan(0, $otp);

        //Max attempts+1 with wrong otp
        for($i=0;$i<self::MAX_ATTEMPTS+1;++$i) {
            //impossible to use expectException because it immediately takes us out of a test method
            try {
                $incorrectOtp = $otp+1;
                $manager->complete($phone, $incorrectOtp);
                $this->fail('WrongOtp was not thrown');
            } catch (\AlexGeno\PhoneVerification\Exception\WrongOtp $e) {
                $this->assertEquals($incorrectOtp, $e->otp());
                $this->assertEquals($phone, $e->phone());
            }
        }

        //correct otp doesn't work anymore
        try {
            $manager->complete($phone, $otp); //correct otp
            $this->fail('MaxAttemptsExceeded was not thrown');
        }catch(\AlexGeno\PhoneVerification\Exception\MaxAttemptsExceeded $e){
            $this->assertEquals($phone, $e->phone());
            $this->assertEquals(self::MAX_ATTEMPTS, $e->maxAttempts());
            $this->assertEquals(self::OTP_EXP_PERIOD, $e->availablePeriod());
        }
    }

    /**
     * @dataProvider phoneNumbers
     * @runInSeparateProcess
     */
    public function testMaxAttempts($phone):void
    {
        $manager = new Manager($this->storageMock,  $this->providerMock, ['max_attempts' => self::MAX_ATTEMPTS]);
        $otp = $manager->start($phone);
        $this->assertGreaterThan(0, $otp);

        //Max attempts with wrong otp
        for($i=0;$i<self::MAX_ATTEMPTS;++$i) {
            //impossible to use expectException because it immediately takes it out of a test method
            try {
                $incorrectOtp = $otp-1;
                $manager->complete($phone, $incorrectOtp); //wrong otp
                $this->fail('WrongOtp was not thrown');
            } catch (\AlexGeno\PhoneVerification\Exception\WrongOtp $e) {
                $this->assertEquals($incorrectOtp, $e->otp());
                $this->assertEquals($phone, $e->phone());
            }
        }

        //correct otp still works
        $self = $manager->complete($phone, $otp);
        $this->assertEquals($manager, $self);
    }

    /**
     * @dataProvider codeLengths
     * @runInSeparateProcess
     */
    public function testOtpLength($phone, $otpLength, $min, $max, $any):void
    {
        $manager = new Manager($this->storageMock, $this->providerMock, ['otp_length' => $otpLength]);

        $rand = $this->getFunctionMock('AlexGeno\PhoneVerification', "rand");
        $rand->expects($this->once())->with($min, $max)->willReturn($any);

        $this->assertEquals($any, $manager->start($phone));
    }
}