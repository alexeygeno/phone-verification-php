<?php

declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Manager;

use AlexGeno\PhoneVerification\Storage\Redis;
use M6Web\Component\RedisMock\RedisMockFactory;
use PHPUnit\Framework\TestCase;
use Predis\Client;

abstract class BaseTest extends TestCase
{
    protected \AlexGeno\PhoneVerification\Sender\I $senderMock;
    protected Redis $storageMock;
    private Client $redisMock;

    protected function setUp(): void
    {
        $this->senderMock = $this->createStub('AlexGeno\PhoneVerification\Sender\Twilio');

        $this->redisMock = (new RedisMockFactory())->getAdapter('\Predis\Client');
        //functional
        //$this->redisMock = new \Predis\Client('redis://redis:6379');
        $this->storageMock  = new Redis($this->redisMock);
    }

    protected function tearDown(): void
    {
        $this->redisMock->flushdb();
    }

    public function phoneNumbers(): array
    {
        return [
            'long' => ['+380935258272'],
            'short'  => ['5417543010'],
        ];
    }
}
