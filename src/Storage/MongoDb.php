<?php

namespace AlexGeno\PhoneVerification\Storage;

use MongoDb\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Collection;

class MongoDb implements I
{
    protected Client $mongoDb;
    protected array $config;

    /**
     * Redis Storage!
     *
     * @param Client $mongoDb
     */
    public function __construct(Client $mongoDb, array $config = [])
    {
        $this->mongoDb = $mongoDb;
        $this->config = array_merge(['db' => 'pvs', 'collection' => 'session'], $config);
    }

    /**
     * @return Collection
     */
    protected function collection(): Collection
    {
        return ($this->mongoDb->{$this->config['db']})->{$this->config['collection']};
    }


    public function setupSession(string $id, int $otp, int $expirationSecs/*, $reset = false*/): I
    {
//        if ($reset) {
//            $this->resetSession($phone);
//        }
        $this->collection()->createIndex(['id' => 1], ['unique' => true]);
        $this->collection()->createIndex(['created' => 1], ['expireAfterSeconds' => $expirationSecs]);

        $session = [
            'id' => $id,
            'otp' => $otp,
            'created' =>  new \MongoDb\BSON\UTCDateTime(),
            'attempts' => 0
        ];
        $this->collection()->updateOne(['id' => $id], ['$set' => $session], ['upsert' => true]);

        return $this;
    }
    public function resetSession(string $id): I
    {
        $this->collection()->deleteOne(['id' => $id]);
        return $this;
    }

    public function otp(string $sessionId): int
    {
        $session = $this->collection()->findOne(['id' => $sessionId], ['projection' => 'otp']);
        return  ($session and !empty($session->otp)) ? $session->otp :  0;
    }

    public function incrementAttempts(string $sessionId): I
    {
        $this->collection()->updateOne(['id' => $sessionId], ['$inc' => ['attempts' => 1]]);
        return $this;
    }
    public function attemptsCount(string $sessionId): int
    {
        $session = $this->collection()->findOne(['id' => $sessionId], ['projection' => 'attempts']);
        return  ($session and !empty($session->attempts)) ? $session->attempts :  0;
    }
}
