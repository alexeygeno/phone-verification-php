<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Manager;

use AlexGeno\PhoneVerification\Exception;
use AlexGeno\PhoneVerification\Exception\Otp;
use AlexGeno\PhoneVerification\Manager;


final class DefaultConfigTest extends BaseTest
{
    protected Manager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new Manager($this->storageMock);
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testCorrectOtp($phone): void
    {
        $this->manager->sender($this->senderMock)->initiate($phone);
        $otp = $this->manager->otp();
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
        $this->manager->sender($this->senderMock)->initiate($phone);
        $otp = $this->manager->otp();

        $this->assertGreaterThan(0, $otp);
        $incorrectOtp = $otp - 1;
        try {
            $this->manager->complete($phone, $incorrectOtp);
            $this->fail('Otp has not been thrown');
        } catch (Otp $e) {
            $this->assertEquals(Otp::CODE_INCORRECT, $e->getCode());
        }
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testExpiredOtpException($phone): void
    {
        $this->manager->sender($this->senderMock)->initiate($phone);
        $otp = $this->manager->otp();

        $this->assertIsInt($otp);
        $this->assertGreaterThan(0, $otp);
        $this->storageMock->sessionDown($phone);//emulate expiration

        try {
            $this->manager->complete($phone, $otp);
            $this->fail('Otp has not been thrown');
        } catch (Otp $e) {
            $this->assertEquals(Otp::CODE_EXPIRED, $e->getCode());
        }
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testInitiationWhenNoSender($phone): void
    {
        $this->expectException(Exception::class);
        $this->manager->initiate($phone);
    }
}
