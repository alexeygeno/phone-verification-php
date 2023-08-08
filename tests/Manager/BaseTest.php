<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Manager;

use AlexGeno\PhoneVerification\Storage\Redis;
use M6Web\Component\RedisMock\RedisMockFactory;
use PHPUnit\Framework\TestCase;
use Predis\Client;

/**
 * Base class to test Manager
 */
abstract class BaseTest extends TestCase
{
    protected \AlexGeno\PhoneVerification\Sender\I $senderMock;
    protected Redis $storageMock;
    private Client $redisMock;

    /**
     * This method is called before each test
     * @return void
     */
    protected function setUp(): void
    {
        $this->senderMock = $this->createMock(\AlexGeno\PhoneVerification\Sender\Twilio::class);
        $this->redisMock = (new RedisMockFactory())->getAdapter('\Predis\Client');
        $this->storageMock  = new Redis($this->redisMock);
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
     * Phone numbers data provider
     * @return string[][]
     */
    public function phoneNumbers(): array
    {
        return [
            'long' => ['+15417543010'],
            'short'  => ['5417543010']
        ];
    }
}
