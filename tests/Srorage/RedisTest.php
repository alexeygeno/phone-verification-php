<?php declare(strict_types=1);
namespace AlexGeno\PhoneVerificationTests\Storage;

use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

use AlexGeno\PhoneVerification\Storage\Redis;
use M6Web\Component\RedisMock\RedisMockFactory;
use Predis\Client;


final class RedisTest extends TestCase
{
    private Redis $redisStorage;

    use PHPMock;

    public function phoneNumbers(): array
    {
        return [
            'UKR' => ['+380935258272'],
            'US'  => ['+15417543010'],
            'UK'  => ['+442077206312']
        ];
    }


    protected function  setUp():void{
        /** @var Client $redisMock */
        $redisMock = (new RedisMockFactory())->getAdapter('\Predis\Client');
        //new mock storage foe every test
        $redisMock->flushdb();
        $this->redisStorage =   new Redis($redisMock);
    }


    /**
     * @dataProvider phoneNumbers
     */
    public function testSessionSetup($phone):void
    {

        $this->redisStorage->setupSession($phone, 12340, 300)
                           ->setupSession($phone, 12345, 10); //no session recreation

        $this->assertEquals(12340, $this->redisStorage->otp($phone));
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testSessionSetupWithReset($phone):void
    {
        $this->redisStorage->setupSession($phone, 1233, 300)
            ->setupSession($phone, 32104, 20, true); //recreate session

        $this->assertEquals(32104, $this->redisStorage->otp($phone));

    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testSessionSetupWithNoReset($phone):void
    {

        $this->redisStorage->setupSession($phone, 12340, 300)
                           ->setupSession($phone, 12345, 300); //no session recreation

        $this->assertEquals(12340, $this->redisStorage->otp($phone));
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testSessionReset($phone):void
    {

        $this->redisStorage->setupSession($phone, 1233, 300)
                           ->incrementAttempts($phone)
                           ->resetSession($phone);

        $this->assertEquals(0, $this->redisStorage->otp($phone));
        $this->assertEquals(0, $this->redisStorage->attemptsCount($phone));

    }


    /**
     * @dataProvider phoneNumbers
     */
    public function testAttempts($phone):void
    {
        $this->redisStorage->setupSession($phone, 2345, 300)
                           ->incrementAttempts($phone);//first attempt

        $this->assertEquals(1, $this->redisStorage->attemptsCount($phone));

        //2 more attempts
        $this->redisStorage->incrementAttempts($phone)->incrementAttempts($phone);

        $this->assertEquals(3, $this->redisStorage->attemptsCount($phone));

    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testExistingOtp($phone):void
    {
        $otp = 566743;
        $this->redisStorage->setupSession($phone, $otp, 300);
        $this->assertEquals($otp, $this->redisStorage->otp($phone));
    }

    /**
     * @dataProvider phoneNumbers
     */
    public function testNonExistingOtp($phone):void
    {
        $this->redisStorage->setupSession($phone, 566743, 300);
        $this->assertEquals(0, $this->redisStorage->otp('+35926663454'));//phone with no otp created beforehand
    }


    /**
     * @dataProvider phoneNumbers
     * @runInSeparateProcess
     */
    public function testSessionExpiration($phoneNumber): void
    {
        //emulate like it's been 20 seconds between the setupSession call and the otp call
        $time = $this->getFunctionMock('M6Web\Component\RedisMock', "time");
        $time->expects($this->exactly(2))->willReturnOnConsecutiveCalls(0, 20);

        //set expiration to 10 seconds
        $this->redisStorage->setupSession($phoneNumber, 566743, 10);

        //check that session doesn't exists
        $otp = $this->redisStorage->otp($phoneNumber);
        $this->assertEquals(0, $otp);
    }

}