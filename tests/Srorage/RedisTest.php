<?php

declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Storage;

use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;
use AlexGeno\PhoneVerification\Storage\Redis;
use M6Web\Component\RedisMock\RedisMockFactory;
use Predis\Client;

final class RedisTest extends BaseTest
{
    use PHPMock;

    protected function setUp(): void
    {
        /** @var Client $redisMock */
        $redisMock = (new RedisMockFactory())->getAdapter('\Predis\Client');
        $this->storage =   new Redis($redisMock);
    }

    /**
     * @dataProvider phoneNumbers
     * @runInSeparateProcess
     */
    public function testExpiration($phone): void
    {
        //emulate like it's been 20 seconds between the sessionUp call and the otp call
        $time = $this->getFunctionMock('M6Web\Component\RedisMock', "time");

        $sessionExpSecs = 300;
        $sessionCounterExpSecs = 3600;

        //emulate that it's been 10 seconds since $sessionExpSecs and $sessionCounterExpSecs
        $time->expects($this->exactly(4))->willReturnOnConsecutiveCalls(0, 0, $sessionExpSecs+10, $sessionCounterExpSecs+10);

        $this->storage->sessionUp($phone, 566743, $sessionExpSecs, $sessionCounterExpSecs);

        //check that session doesn't exists
        $otp = $this->storage->otp($phone);
        $this->assertEquals(0, $otp);

        //check that sessionCounter doesn't exists
        $this->assertEquals(0, $this->storage->sessionCounter($phone));
    }
}
