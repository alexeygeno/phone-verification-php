<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Storage;

use MongoDB\Client;
use MongoDB\Collection;

/**
 * MongoDb storage implementation
 */
class MongoDb implements I
{
    protected Client $client;
    protected array $config;


    /**
     * MongoDb constructor
     *
     * @param Client $client
     * @param array  $config
     * Every param has a default value and could be replaced
     * [
     *     'db' => 'phone_verification',
     *     'collection_session' => 'session',
     *     'collection_session_counter' => 'session_counter',
     *     'indexes' => false
     * ]
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
     * Returns collection of the current db by its name
     *
     * @param string $name
     * @return Collection
     */
    protected function collection(string $name): Collection
    {
        return ($this->client->{$this->config['db']})->{$name};
    }

    /**
     * Creates a session and increments its counter
     *
     * @param string  $sessionId
     * @param integer $otp
     * @param integer $sessionExpSecs
     * @param integer $sessionCounterExpSecs
     * @return $this
     */
    public function sessionUp(string $sessionId, int $otp, int $sessionExpSecs, int $sessionCounterExpSecs): MongoDb
    {
        // phpcs:disable Squiz.Commenting.BlockComment.SingleLine

        /*
            TODO: make the transaction execution optional via config param $atomicity
            @link https://www.mongodb.com/docs/upcoming/core/transactions/
            throws MongoDB\Driver\Exception\BulkWriteException: Transaction numbers are only allowed on a replica set member or mongos
            $transaction = $this->client->startSession();
            $callback = function (\MongoDB\Driver\Session $session) use ($otp, $sessionId): void {
        */

            $sessionSet = [
                'otp' => $otp,
                // New datetime after every update!
                'updated' => new \MongoDb\BSON\UTCDateTime(),
                // Reset otp_check_count every update!
                'otp_check_count' => 0
            ];

            $this->collection($this->config['collection_session'])
                    ->updateOne(['id' => $sessionId], ['$set' => $sessionSet], ['upsert' => true/*, 'session'=> $session*/]);

            $sessionCounterSetOnInsert = [
                // New datetime on creation, no changes on update!
                'created' => new \MongoDb\BSON\UTCDateTime()
            ];

            $this->collection($this->config['collection_session_counter'])
                    ->updateOne(['id' => $sessionId], ['$setOnInsert' => $sessionCounterSetOnInsert], ['upsert' => true, /*'session'=> $session*/]);
            $this->collection($this->config['collection_session_counter'])
                    ->updateOne(['id' => $sessionId], ['$inc' => ['count' => 1]], [/*'session'=> $session*/]);

        /*
            };
            \MongoDB\with_transaction($transaction, $callback);
        */

        // phpcs:enable

            // Indexes
            if ($this->config['indexes']) {
                $this->createIndexes($sessionExpSecs, $sessionCounterExpSecs);
            }

            return $this;
    }

    /**
     * Creates indexes for a session collection end a session_counter collection
     * NOT RECOMMENDED to use it on production
     *
     * @param integer $sessionExpSecs
     * @param integer $sessionCounterExpSecs
     * @return void
     */
    protected function createIndexes(int $sessionExpSecs, int $sessionCounterExpSecs): void
    {
        $this->collection($this->config['collection_session'])
                ->createIndex(['id' => 1], ['unique' => true]);
        $this->collection($this->config['collection_session'])
                ->createIndex(['updated' => 1], ['expireAfterSeconds' => $sessionExpSecs]);

        $this->collection($this->config['collection_session_counter'])
                ->createIndex(['id' => 1], ['unique' => true]);
        $this->collection($this->config['collection_session_counter'])
                ->createIndex(['created' => 1], ['expireAfterSeconds' => $sessionCounterExpSecs]);
    }

    /**
     * Drops session by its id
     *
     * @param string $sessionId
     * @return $this
     */
    public function sessionDown(string $sessionId): MongoDb
    {
        $this->collection($this->config['collection_session'])->deleteOne(['id' => $sessionId]);
        return $this;
    }

    /**
     * Returns the amount of recreated sessions
     *
     * @param string $sessionId
     * @return integer
     */
    public function sessionCounter(string $sessionId): int
    {
        $sessionCounter = $this->collection($this->config['collection_session_counter'])
                               ->findOne(['id' => $sessionId], ['projection' => ['count' => 1]]);
        return  ($sessionCounter and !empty($sessionCounter->count)) ? $sessionCounter->count : 0;
    }

    /**
     * Returns session otp
     *
     * @param string $sessionId
     * @return integer
     */
    public function otp(string $sessionId): int
    {
        $session = $this->collection($this->config['collection_session'])->findOne(['id' => $sessionId], ['projection' => ['otp' => 1]]);
        return  ($session and !empty($session->otp)) ? $session->otp :  0;
    }

    /**
     * Increments the amount of otp checks for the session
     *
     * @param string $sessionId
     * @return $this
     */
    public function otpCheckIncrement(string $sessionId): MongoDb
    {
        $this->collection($this->config['collection_session'])->updateOne(['id' => $sessionId], ['$inc' => ['otp_check_count' => 1]]);
        return $this;
    }

    /**
     * Returns the amount of otp checks for the session
     *
     * @param string $sessionId
     * @return integer
     */
    public function otpCheckCounter(string $sessionId): int
    {
        $session = $this->collection($this->config['collection_session'])->findOne(['id' => $sessionId], ['projection' => ['otp_check_count' => 1]]);
        return  ($session and !empty($session->otp_check_count)) ? $session->otp_check_count :  0;
    }
}
