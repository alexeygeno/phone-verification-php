<?php

declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Storage;

use MongoDB\Client;
use PHPUnit\Framework\TestCase;
use Helmich\MongoMock\MockDatabase;
use AlexGeno\PhoneVerification\Storage\MongoDb;

/**
 * Class MongoDbTest
 * @package AlexGeno\PhoneVerificationTests\Storage
 */
final class MongoDbTest extends BaseTest
{
    protected Client $mongoDbMock;
    protected function setUp(): void
    {
        parent::setUp();
        $this->mongoDbMock = $this->createMock('\MongoDB\Client');
        $this->mongoDbMock->expects($this->atLeastOnce())
                    ->method('__get')
                    ->willReturn( new MockDatabase('phone_verification'));

        //functional
        //$this->mongoDbMock = new \MongoDB\Client('mongodb://mongodb:27017/');
//
        $this->storage = new MongoDb($this->mongoDbMock, ['indexes' => 'true', 'db' => 'phone_verification']);
    }

    protected function tearDown(): void
    {
        $this->mongoDbMock->dropDatabase('phone_verification');
    }


}
