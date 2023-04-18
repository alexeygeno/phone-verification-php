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
        return new Redis(new \Predis\Client('redis://redis:6379'));
    }

    /**
     * Returns MongoDb client instance
     * @return MongoDb
     */
    public function mongoDb(): MongoDb
    {
        return new MongoDb(new \MongoDB\Client('mongodb://mongodb:27017/'), ['indexes' => 'true', 'db' => 'phone_verification']);
    }
}
