<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Storage;

use MongoDB\Client;
use MongoDB\Collection;

class MongoDb implements I
{
    protected Client $client;
    protected array $config;

    /**
     * Redis Storage!
     *
     * @param Client client
     */
    public function __construct(Client $client, array $config = [])
    {
        $this->client = $client;
        $this->config = array_replace(['db' => 'phone_verification',
                                       'collection_session' => 'session',
                                       'collection_session_counter' => 'session_counter',
                                       'indexes' => false], $config);
    }

    /**
     * @return Collection
     */
    protected function collection($name): Collection
    {
        return ($this->client->{$this->config['db']})->{$name};
    }

    public function sessionUp(string $sessionId, int $otp, int $sessionExpSecs, int $sessionCounterExpSecs): I
    {

        //TODO: make the transaction execution optional via config param $atomicity
        //throws MongoDB\Driver\Exception\BulkWriteException: Transaction numbers are only allowed on a replica set member or mongos
        //$transaction = $this->client->startSession();
        //$callback = function (\MongoDB\Driver\Session $session) use ($otp, $sessionId): void {

            $sessionSet = [
                'otp' => $otp,
                //new datetime after every update
                'updated' => new \MongoDb\BSON\UTCDateTime(),
                //reset otp_check_count every update
                'otp_check_count' => 0
            ];

            $this->collection($this->config['collection_session'])->updateOne(['id' => $sessionId], ['$set' => $sessionSet], ['upsert' => true/*, 'session'=> $session*/]);

            $sessionCounterSetOnInsert = [
                //new datetime on creation, no changes after update
                'created' => new \MongoDb\BSON\UTCDateTime()
            ];

            $this->collection($this->config['collection_session_counter'])->updateOne(['id' => $sessionId], ['$setOnInsert' => $sessionCounterSetOnInsert], ['upsert' => true, /*'session'=> $session*/]);
            $this->collection($this->config['collection_session_counter'])->updateOne(['id' => $sessionId], ['$inc' => ['count' => 1]], [/*'session'=> $session*/]);

        //};
        //@link https://www.mongodb.com/docs/upcoming/core/transactions/
        //\MongoDB\with_transaction($transaction, $callback);


        //indexes
        if($this->config['indexes']){
            $this->createIndexes( $sessionExpSecs,  $sessionCounterExpSecs);
        }

        return $this;
    }

    /**
     * @param int $sessionExpSecs
     * @param int $sessionCounterExpSecs
     */
    protected function createIndexes(int $sessionExpSecs, int $sessionCounterExpSecs){
        $this->collection($this->config['collection_session'])->createIndex(['id' => 1], ['unique' => true]);
        $this->collection($this->config['collection_session'])->createIndex(['updated' => 1], ['expireAfterSeconds' => $sessionExpSecs]);

        $this->collection($this->config['collection_session_counter'])->createIndex(['id' => 1], ['unique' => true]);
        $this->collection($this->config['collection_session_counter'])->createIndex(['created' => 1], ['expireAfterSeconds' => $sessionCounterExpSecs]);
    }

    public function sessionDown(string $sessionId): I
    {
        $this->collection($this->config['collection_session'])->deleteOne(['id' => $sessionId]);
        return $this;
    }

    public function otp(string $sessionId): int
    {
        $session = $this->collection($this->config['collection_session'])->findOne(['id' => $sessionId], ['projection' => ['otp' => 1]]);
        return  ($session and !empty($session->otp)) ? $session->otp :  0;
    }

    public function otpCheckIncrement(string $sessionId): I
    {
        $this->collection($this->config['collection_session'])->updateOne(['id' => $sessionId], ['$inc' => ['otp_check_count' => 1]]);
        return $this;
    }
    public function otpCheckCounter(string $sessionId): int
    {
        $session = $this->collection($this->config['collection_session'])->findOne(['id' => $sessionId], ['projection' => ['otp_check_count' => 1]]);
        return  ($session and !empty($session->otp_check_count)) ? $session->otp_check_count :  0;
    }

    public function sessionCounter(string $sessionId): int{
        $sessionCounter = $this->collection($this->config['collection_session_counter'])->findOne(['id' => $sessionId], ['projection' => ['count' => 1]]);
        return  ($sessionCounter and !empty($sessionCounter->count)) ? $sessionCounter->count : 0;

    }
}
