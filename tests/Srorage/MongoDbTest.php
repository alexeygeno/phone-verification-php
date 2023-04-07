<?php

declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Storage;

use PHPUnit\Framework\TestCase;
use Helmich\MongoMock\MockDatabase;
use AlexGeno\PhoneVerification\Storage\MongoDb;

final class MongoDbTest extends BaseTest
{
    protected function setUp(): void
    {
        $db = 'phone_verification';
        $mongoDbMock = $this->createMock('\MongoDB\Client');
        $mockDatabase = new MockDatabase($db);
        $mongoDbMock->expects($this->atLeastOnce())
            ->method('__get')
            ->willReturn($mockDatabase);
        $this->storage = new MongoDb($mongoDbMock, ['db' => $db]);
    }
}
