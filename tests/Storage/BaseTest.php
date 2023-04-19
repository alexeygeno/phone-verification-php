<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Storage;

use PHPUnit\Framework\TestCase;
use AlexGeno\PhoneVerification\Storage\I;

/**
 * Base class to test a storage
 */
abstract class BaseTest extends TestCase
{
    protected I $storage;

    /**
     * Phone numbers data provider
     * @return string[][]
     */
    public function phoneNumbers(): array
    {
        return [
            'long' => ['+380935258272'],
            'short'  => ['5417543010']
        ];
    }

    /**
     * Checks if session exists after the creation
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testSessionCreate(string $phone): void
    {
        $this->storage->sessionUp($phone, 12340, 300, 3600);
        $this->assertEquals(12340, $this->storage->otp($phone));
    }

    /**
     * Checks if session data was updated after recreation
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testSessionRecreate(string $phone): void
    {
        $this->storage->sessionUp($phone, 1233, 300, 3600 * 2)
                      ->sessionUp($phone, 32104, 300, 3600 * 2);
        // Session recreation
        $this->assertEquals(32104, $this->storage->otp($phone));
    }

    /**
     * Checks if session counter works as expected
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testSessionCounter(string $phone): void
    {
        $otp = 2222;
        $sessionExpSecs = 60;
        $sessionCounterExpSecs = 60;
        $this->storage->sessionUp($phone, $otp, $sessionExpSecs, $sessionCounterExpSecs);

        $this->assertEquals(1, $this->storage->sessionCounter($phone));

        // 2 more session creation
        $this->storage->sessionUp($phone, $otp, $sessionExpSecs, $sessionCounterExpSecs)
                      ->sessionUp($phone, $otp, $sessionExpSecs, $sessionCounterExpSecs);

        $this->assertEquals(3, $this->storage->sessionCounter($phone));
    }

    /**
     * Checks if otp is empty when the session was deleted beforehand
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testOtpAfterSessionDown(string $phone): void
    {
        $this->storage->sessionUp($phone, 566743, 300, 3600 * 4);
        $this->storage->sessionDown($phone);
        $this->assertEquals(0, $this->storage->otp($phone));
    }

    /**
     * Checks if otp is empty for a non-existing session
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testOtpForNonExistingSession(string $phone): void
    {
        $this->storage->sessionUp($phone, 566743, 300, 3600 * 4);
        // No session created beforehand for this phone number
        $this->assertEquals(0, $this->storage->otp('+35926663454'));
    }

    /**
     * Checks if otpCheckCounter is empty when the session was deleted beforehand
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testOtpCheckCounterAfterSessionDown(string $phone): void
    {
        $this->storage->sessionUp($phone, 1233, 300, 3600)
                      ->otpCheckIncrement($phone)
                      ->sessionDown($phone);

        $this->assertEquals(0, $this->storage->otp($phone));
        $this->assertEquals(0, $this->storage->otpCheckCounter($phone));
    }

    /**
     * Checks if otpCheckCounter works as expected
     *
     * @dataProvider phoneNumbers
     * @param string $phone
     * @return void
     */
    public function testOtpCheckCounter(string $phone): void
    {
        $this->storage->sessionUp($phone, 2345, 300, 3600 * 3)
                      // First attempt
                      ->otpCheckIncrement($phone);
        $this->assertEquals(1, $this->storage->otpCheckCounter($phone));

        // 2 more attempts
        $this->storage->otpCheckIncrement($phone)->otpCheckIncrement($phone);
        $this->assertEquals(3, $this->storage->otpCheckCounter($phone));
    }
}
