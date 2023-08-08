<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Storage;

use MongoDB\Client;
use Helmich\MongoMock\MockDatabase;
use AlexGeno\PhoneVerification\Storage\MongoDb;

/**
 * Test the MongoDb storage
 */
final class MongoDbTest extends BaseTest
{
    protected Client $mongoDbMock;

    /**
     * This method is called before each test
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mongoDbMock = $this->createMock(\MongoDB\Client::class);
        $this->mongoDbMock->expects($this->atLeastOnce())
                          ->method('__get')
                          ->willReturn(new MockDatabase('phone_verification'));
        $this->storage = new MongoDb($this->mongoDbMock, ['indexes' => 'true', 'db' => 'phone_verification']);
    }

    /**
     * This method is called after each test
     * @return void
     */
    protected function tearDown(): void
    {
        $this->mongoDbMock->dropDatabase('phone_verification');
    }
}
