<?php

declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Manager;

use AlexGeno\PhoneVerification\Exception\RateLimit;
use AlexGeno\PhoneVerification\Exception\Otp;
use AlexGeno\PhoneVerification\Manager;


final class DefaultConfigTest extends BaseTest
{
    protected Manager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new Manager($this->storageMock, $this->senderMock);
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testCorrectOtp($phone): void
    {
        $otp = $this->manager->start($phone);
        $this->assertIsInt($otp);
        $this->assertGreaterThan(0, $otp);
        $self = $this->manager->complete($phone, $otp);
        $this->assertEquals($self, $this->manager);
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testIncorrectOtpException($phone): void
    {
        $otp = $this->manager->start($phone);
        $this->assertGreaterThan(0, $otp);
        $incorrectOtp = $otp - 1;
        try {
            $this->manager->complete($phone, $incorrectOtp);
            $this->fail('Otp was not thrown');
        } catch (Otp $e) {
            $this->assertEquals($incorrectOtp, $e->otp());
            $this->assertEquals($phone, $e->phone());
        }
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testNoSessionOtpException($phone): void
    {
        $otp = $this->manager->start($phone);
        $this->assertIsInt($otp);
        $this->assertGreaterThan(0, $otp);
        $this->storageMock->sessionDown($phone);//emulate expiration

        try {
            $this->manager->complete($phone, $otp);
            $this->fail('Otp was not thrown');
        } catch (Otp $e) {
            $this->assertEquals($otp, $e->otp());
            $this->assertEquals($phone,$e->phone());
        }
    }
}
