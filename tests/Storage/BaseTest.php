<?php

declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Storage;

use PHPUnit\Framework\TestCase;
use AlexGeno\PhoneVerification\Storage\I;

abstract class BaseTest extends TestCase
{
    protected I $storage;

    public function phoneNumbers(): array
    {
        return [
            'UKR' => ['+380935258272'],
            'US' => ['5417543010'],
            'UK' => ['+442077206312']
        ];
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testSessionSetup($phone): void
    {
        $this->storage->sessionUp($phone, 12340, 300, 3600);
        $this->assertEquals(12340, $this->storage->otp($phone));
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testSessionReSetup($phone): void
    {
        $this->storage->sessionUp($phone, 1233, 300, 3600*2)
                      ->sessionUp($phone, 32104, 300, 3600*2); //session recreation
        $this->assertEquals(32104, $this->storage->otp($phone));
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testSessionReset($phone): void
    {

        $this->storage->sessionUp($phone, 1233, 300, 3600)
            ->otpCheckIncrement($phone)
            ->sessionDown($phone);

        $this->assertEquals(0, $this->storage->otp($phone));
        $this->assertEquals(0, $this->storage->otpCheckCounter($phone));
    }


    /**
     * @dataProvider phoneNumbers
     */
    public function testOtpCheckCounter($phone): void
    {
        $this->storage->sessionUp($phone, 2345, 300, 3600*3)
            ->otpCheckIncrement($phone);//first attempt

        $this->assertEquals(1, $this->storage->otpCheckCounter($phone));

        //2 more attempts
        $this->storage->otpCheckIncrement($phone)->otpCheckIncrement($phone);

        $this->assertEquals(3, $this->storage->otpCheckCounter($phone));
    }


    /**
     * @dataProvider phoneNumbers
     */
    public function testSessionDown($phone): void
    {
        $this->storage->sessionUp($phone, 566743, 300, 3600*4);
        $this->storage->sessionDown($phone);
        $this->assertEquals(0, $this->storage->otp($phone));
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testNonExistingOtp($phone): void
    {
        $this->storage->sessionUp($phone, 566743, 300, 3600*4);
        $this->assertEquals(0, $this->storage->otp('+35926663454'));//phone with no session created beforehand
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testSessionCounter($phone): void
    {
        $otp = 2222;
        $sessionExpSecs = 60;
        $sessionCounterExpSecs = 60;
        $this->storage->sessionUp($phone, $otp, $sessionExpSecs, $sessionCounterExpSecs);

        $this->assertEquals(1, $this->storage->sessionCounter($phone));

        //2 more session creation
        $this->storage->sessionUp($phone, $otp, $sessionExpSecs, $sessionCounterExpSecs)
            ->sessionUp($phone, $otp, $sessionExpSecs, $sessionCounterExpSecs);

        $this->assertEquals(3, $this->storage->sessionCounter($phone));
    }

}
