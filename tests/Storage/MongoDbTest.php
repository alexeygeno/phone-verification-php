<?php

declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Storage;

use PHPUnit\Framework\TestCase;
use Helmich\MongoMock\MockDatabase;
use AlexGeno\PhoneVerification\Storage\MongoDb;

/**
 * Class MongoDbTest
 * @package AlexGeno\PhoneVerificationTests\Storage
 */
final class MongoDbTest extends BaseTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $db = 'phone_verification';
//        $mongoDbMock = $this->createMock('\MongoDB\Client');
//        $mockDatabase = new MockDatabase($db);
//        $mongoDbMock->expects($this->atLeastOnce())
//            ->method('__get')
//            ->willReturn($mockDatabase);

        //functional
        $mongoDbMock = new \MongoDB\Client('mongodb://mongodb:27017');
        $mongoDbMock->dropDatabase($db);

        $this->storage = new MongoDb($mongoDbMock, ['db' => $db]);
    }


}
