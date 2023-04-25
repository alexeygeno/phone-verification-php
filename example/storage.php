<?php declare(strict_types=1);

use AlexGeno\PhoneVerification\Storage\MongoDb;
use AlexGeno\PhoneVerification\Storage\Redis;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
/**
 * Class Storage
 * Storage factory - creates storage clients instances
 */
class Storage
{
    /**
     * Returns Redis client instance
     * @return Redis
     */
    public function redis(): Redis
    {
        return new Redis(new \Predis\Client(getenv('REDIS_CONNECTION')));
    }

    /**
     * Returns MongoDb client instance
     * @return MongoDb
     */
    public function mongoDb(): MongoDb
    {
        return new MongoDb(new \MongoDB\Client(getenv('MONGODB_CONNECTION')), ['indexes' => 'true', 'db' => 'phone_verification']);
    }
}
