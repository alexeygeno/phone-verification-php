<?php

declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Storage;

use PHPUnit\Framework\TestCase;
use Helmich\MongoMock\MockDatabase;
use AlexGeno\PhoneVerification\Storage\MongoDb;

final class MongoDbTest extends TestCase
{
    private MongoDb $mongoDbStorage;

    public function phoneNumbers(): array
    {
        return [
            'UKR' => ['+380935258272'],
            'US' => ['5417543010'],
            'UK' => ['+442077206312']
        ];
    }


    protected function setUp(): void
    {
        $mongoDbMock = $this->createMock('\MongoDB\Client');
        $mockDatabase = new MockDatabase();
        $mongoDbMock->expects($this->atLeastOnce())
            ->method('__get')
            ->willReturn($mockDatabase);
        $this->mongoDbStorage = new MongoDb($mongoDbMock);
    }


    /**
     * @dataProvider phoneNumbers
     */
    public function testSessionSetup($phone): void
    {
        $this->mongoDbStorage->setupSession($phone, 12340, 300);
        $this->assertEquals(12340, $this->mongoDbStorage->otp($phone));
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testSessionReSetup($phone): void
    {
        $this->mongoDbStorage->setupSession($phone, 1233, 300)
                            ->setupSession($phone, 32104, 20); //recreate session
        $this->assertEquals(32104, $this->mongoDbStorage->otp($phone));
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testSessionReset($phone): void
    {

        $this->mongoDbStorage->setupSession($phone, 1233, 300)
            ->incrementAttempts($phone)
            ->resetSession($phone);

        $this->assertEquals(0, $this->mongoDbStorage->otp($phone));
        $this->assertEquals(0, $this->mongoDbStorage->attemptsCount($phone));
    }


    /**
     * @dataProvider phoneNumbers
     */
    public function testAttempts($phone): void
    {
        $this->mongoDbStorage->setupSession($phone, 2345, 300)
            ->incrementAttempts($phone);//first attempt

        $this->assertEquals(1, $this->mongoDbStorage->attemptsCount($phone));

        //2 more attempts
        $this->mongoDbStorage->incrementAttempts($phone)->incrementAttempts($phone);

        $this->assertEquals(3, $this->mongoDbStorage->attemptsCount($phone));
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testExistingOtp($phone): void
    {
        $otp = 566743;
        $this->mongoDbStorage->setupSession($phone, $otp, 300);
        $this->assertEquals($otp, $this->mongoDbStorage->otp($phone));
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testNonExistingOtp($phone): void
    {
        $this->mongoDbStorage->setupSession($phone, 566743, 300);
        $this->assertEquals(0, $this->mongoDbStorage->otp('+35926663454'));//phone with no session created beforehand
    }
}
