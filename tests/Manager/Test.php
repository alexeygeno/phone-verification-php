<?php declare(strict_types=1);
namespace AlexGeno\PhoneVerificationTests\Manager;

use AlexGeno\PhoneVerification\Exception\ExpiredOtp;
use AlexGeno\PhoneVerification\Exception\WrongOtp;
use AlexGeno\PhoneVerification\Manager;
use AlexGeno\PhoneVerification\Storage\Redis;
use M6Web\Component\RedisMock\RedisMockFactory;
use Predis\Client;


final class Test extends BaseTest
{
    protected Manager $manager;

    protected function  setUp():void{
        parent::setUp();
        $this->manager = new Manager($this->storageMock, $this->providerMock);
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testCorrectOtp($phone):void
    {
        $otp = $this->manager->start($phone);
        $this->assertIsInt($otp);
        $self = $this->manager->complete($phone, $otp);
        $this->assertEquals($self, $this->manager);
    }

//    /**
//     * @dataProvider phoneNumbers
//     */
//    public function testIncorrectOtp($phoneNumber):void
//    {
//        $otp = $this->manager->start($phoneNumber);
//        $this->assertGreaterThan(0, $otp);
//        $this->expectException(WrongOtp::class);
//        $this->manager->complete($phoneNumber, $otp-1);
//    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testIncorrectOtpException($phone):void
    {
        $otp = $this->manager->start($phone);
        $this->assertGreaterThan(0, $otp);
        $incorrectOtp = $otp-1;
        try {
            $this->manager->complete($phone, $incorrectOtp);
            $this->fail('ExpiredOtp was not thrown');
        }catch  (WrongOtp $e){
            $this->assertEquals($incorrectOtp, $e->otp());
            $this->assertEquals($phone, $e->phone());
        }
    }

//    /**
//     * @dataProvider phoneNumbers
//     */
//    public function testExpiredOtp($phoneNumber):void
//    {
//        $otp = $this->manager->start($phoneNumber);
//        $this->assertIsInt($otp);
//
//        $this->storageMock->resetSession($phoneNumber);//emulate expiration
//        $this->expectException(ExpiredOtp::class);
//        $this->manager->complete($phoneNumber, $otp);
//    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testExpiredOtpException($phone):void
    {
        $otp = $this->manager->start($phone);
        $this->assertIsInt($otp);
        $this->assertGreaterThan(0, $otp);
        $this->storageMock->resetSession($phone);//emulate expiration

        try {
            $this->manager->complete($phone, $otp);
            $this->fail('ExpiredOtp was not thrown');
        }catch  (ExpiredOtp $e){
            $this->assertEquals($otp, $e->otp());
            $this->assertEquals($phone, $e->phone());
        }
    }


    /**
     * @dataProvider phoneNumbers
     */
    public function testNonExpiredOtp($phone):void
    {
        $otp = $this->manager->start($phone);
        $this->assertIsInt($otp);

        $self = $this->manager->complete($phone, $otp);
        $this->assertEquals($this->manager, $self);
    }

}
