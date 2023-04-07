<?php

namespace AlexGeno\PhoneVerification\Storage;

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
        $this->config = array_replace(['db' => 'phone_verification', 'collection_session' => 'session',
                                    'collection_session_counter'=>'session_counter'], $config);
    }

    /**
     * @return Collection
     */
    protected function collection($name): Collection
    {
        return ($this->mongoDb->{$this->config['db']})->{$name};
    }


    public function sessionUp(string $sessionId, int $otp, int $sessionExpSecs, int $sessionCounterExpSecs): I
    {

        //session upsert
        $session = [
            'id' => $sessionId,
            'otp' => $otp,
            'created' =>  new \MongoDb\BSON\UTCDateTime(),
            'attempts' => 0
        ];

        $this->collection($this->config['collection_session'])->updateOne(['id' => $sessionId], ['$set' => $session], ['upsert' => true]);
        //indexes
        $this->collection($this->config['collection_session'])->createIndex(['id' => 1], ['unique' => true]);
        $this->collection($this->config['collection_session'])->createIndex(['created' => 1], ['expireAfterSeconds' => $sessionExpSecs]);

        //session counter upsert
        $sessionCounter = [
            'id' => $sessionId,
            'created' =>  new \MongoDb\BSON\UTCDateTime()
        ];

        $this->collection($this->config['collection_session_counter'])->updateOne(['id' => $sessionId], ['$set' => $sessionCounter, '$inc' => ['count' => 1]], ['upsert' => true]);
        //indexes
        $this->collection($this->config['collection_session_counter'])->createIndex(['id' => 1], ['unique' => true]);
        $this->collection($this->config['collection_session_counter'])->createIndex(['created' => 1], ['expireAfterSeconds' => $sessionExpSecs]);

        return $this;
    }
    public function sessionDown(string $sessionId): I
    {
        $this->collection($this->config['collection_session'])->deleteOne(['id' => $sessionId]);
        return $this;
    }

    public function otp(string $sessionId): int
    {
        $session = $this->collection($this->config['collection_session'])->findOne(['id' => $sessionId], ['projection' => 'otp']);
        return  ($session and !empty($session->otp)) ? $session->otp :  0;
    }

    public function otpCheckIncrement(string $sessionId): I
    {
        $this->collection($this->config['collection_session'])->updateOne(['id' => $sessionId], ['$inc' => ['attempts' => 1]]);
        return $this;
    }
    public function otpCheckCounter(string $sessionId): int
    {
        $session = $this->collection($this->config['collection_session'])->findOne(['id' => $sessionId], ['projection' => 'attempts']);
        return  ($session and !empty($session->attempts)) ? $session->attempts :  0;
    }

    public function sessionCounter(string $sessionId): int{
        $this->collection($this->config['collection_session_counter'])->updateOne(['id' => $sessionId], ['$inc' => ['count' => 1]]);
    }
}
