<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Storage;

use phpmock\phpunit\PHPMock;
use AlexGeno\PhoneVerification\Storage\Redis;
use M6Web\Component\RedisMock\RedisMockFactory;
use Predis\Client;

/**
 * Test the Redis storage
 */
final class RedisTest extends BaseTest
{
    use PHPMock;

    protected Client $redisMock;

    /**
     * This method is called before each test
     * @return void
     */
    protected function setUp(): void
    {
        $this->redisMock = (new RedisMockFactory())->getAdapter('\Predis\Client');
        $this->storage = new Redis($this->redisMock);
    }

    /**
     * This method is called after each test
     * @return void
     */
    protected function tearDown(): void
    {
        $this->redisMock->flushdb();
    }

    /**
     * Checks if the OTP expiration works as expected
     *
     * @dataProvider phoneNumbers
     * @runInSeparateProcess
     * @link https://github.com/php-mock/php-mock-phpunit#restrictions
     * @param string $phone
     * @return void
     */
    public function testOtpExpiration(string $phone): void
    {
        $time = $this->getFunctionMock('M6Web\Component\RedisMock', "time");

        $sessionExpSecs = 300;

        // Emulate that it's been 10 seconds since $sessionExpSecs
        $time->expects($this->exactly(3))->willReturnOnConsecutiveCalls(0, 0, $sessionExpSecs + 10);

        $this->storage->sessionUp($phone, 566743, $sessionExpSecs, 100);

        // Make sure the otp doesn't exist
        $otp = $this->storage->otp($phone);
        $this->assertEquals(0, $otp);
    }

    /**
     * Checks if the session counter expiration works as expected
     *
     * @dataProvider phoneNumbers
     * @runInSeparateProcess
     * @link https://github.com/php-mock/php-mock-phpunit#restrictions
     * @param string $phone
     * @return void
     */
    public function testSessionCounterExpiration(string $phone): void
    {
        $time = $this->getFunctionMock('M6Web\Component\RedisMock', "time");

        $sessionCounterExpSecs = 3600;

        // Emulate that it's been 10 seconds since $sessionCounterExpSecs
        $time->expects($this->exactly(3))->willReturnOnConsecutiveCalls(0, 0, $sessionCounterExpSecs + 10);

        $this->storage->sessionUp($phone, 566743, 100, $sessionCounterExpSecs);

        // Make sure the session counter doesn't exists
        $this->assertEquals(0, $this->storage->sessionCounter($phone));
    }
}
