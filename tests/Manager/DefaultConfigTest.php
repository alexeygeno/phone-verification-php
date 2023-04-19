<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Manager;

use AlexGeno\PhoneVerification\Exception;
use AlexGeno\PhoneVerification\Exception\Otp;
use AlexGeno\PhoneVerification\Manager;

/**
 * Class to test Manager with a default config
 */
final class DefaultConfigTest extends BaseTest
{
    protected Manager $manager;

    /**
     * This method is called before each test
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new Manager($this->storageMock);
    }

    /**
     * Checks if the verification process goes as expected using a correct otp to complete
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testCorrectOtp(string $phone): void
    {
        $this->manager->sender($this->senderMock)->initiate($phone);
        $otp = $this->manager->otp();
        $this->assertIsInt($otp);
        $this->assertGreaterThan(0, $otp);
        $self = $this->manager->complete($phone, $otp);
        $this->assertEquals($self, $this->manager);
    }

    /**
     * Checks if the verification process goes as expected using an incorrect otp to complete
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testIncorrectOtpException(string $phone): void
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
     * Checks if the verification process goes as expected using an expired otp to complete
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testExpiredOtpException(string $phone): void
    {
        $this->manager->sender($this->senderMock)->initiate($phone);
        $otp = $this->manager->otp();

        $this->assertIsInt($otp);
        $this->assertGreaterThan(0, $otp);

        // Emulate expiration
        $this->storageMock->sessionDown($phone);
        try {
            $this->manager->complete($phone, $otp);
            $this->fail('Otp has not been thrown');
        } catch (Otp $e) {
            $this->assertEquals(Otp::CODE_EXPIRED, $e->getCode());
        }
    }

    /**
     * Checks if the initiation without a sender throws Exception
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testInitiationWhenNoSender(string $phone): void
    {
        $this->expectException(Exception::class);
        $this->manager->initiate($phone);
    }
}
