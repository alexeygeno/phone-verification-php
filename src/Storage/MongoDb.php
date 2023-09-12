<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Storage;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

/**
 * MongoDb storage implementation
 */
class MongoDb implements I
{
    protected Client $client;

    /**
     * @see https://www.php.net/manual/en/mongodb-driver-cursor.settypemap.php
     */
    private const TYPE_MAP = [
        'array' => BSONArray::class,
        'document' => BSONDocument::class,
        'root' => BSONDocument::class,
    ];

    /**
     * @var array<mixed>
     */
    protected array $config;

    /**
     * Constructor
     *
     * @param Client       $client
     * @param array<mixed> $config
     * Every param has a default value and can be replaced
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
     * Creates MongoDb expiration indexes
     * DEV ONLY! NOT RECOMMENDED to call this function in production
     *
     * @param integer $sessionExpSecs
     * @param integer $sessionCounterExpSecs
     * @return void
     */
    private function createIndexes(int $sessionExpSecs, int $sessionCounterExpSecs): void
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
     * Returns a collection by its name
     *
     * @param string $name
     * @return Collection
     */
    protected function collection(string $name): Collection
    {
        return ($this->client->{$this->config['db']})->{$name};
    }

    /**
     * {@inheritdoc}
     */
    public function sessionUp(string $sessionId, int $otp, int $sessionExpSecs, int $sessionCounterExpSecs): self
    {
        $sessionSet = [
            'otp' => $otp,
            // New datetime after every update!
            'updated' => new \MongoDb\BSON\UTCDateTime(),
            // Reset otp_check_count every update!
            'otp_check_count' => 0
        ];

        $this->collection($this->config['collection_session'])
             ->updateOne(['id' => $sessionId], ['$set' => $sessionSet], ['upsert' => true]);

        $sessionCounterSetOnInsert = [
            // New datetime on creation, no changes on update!
            'created' => new \MongoDb\BSON\UTCDateTime()
        ];

        $this->collection($this->config['collection_session_counter'])
             ->updateOne(['id' => $sessionId], ['$setOnInsert' => $sessionCounterSetOnInsert], ['upsert' => true]);
        $this->collection($this->config['collection_session_counter'])
             ->updateOne(['id' => $sessionId], ['$inc' => ['count' => 1]]);

        // Indexes
        if ($this->config['indexes']) {
            $this->createIndexes($sessionExpSecs, $sessionCounterExpSecs);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function sessionDown(string $sessionId): self
    {
        $this->collection($this->config['collection_session'])->deleteOne(['id' => $sessionId]);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function sessionCounter(string $sessionId): int
    {
        $sessionCounter = $this->collection($this->config['collection_session_counter'])
                               ->findOne(['id' => $sessionId], ['projection' => ['count' => 1], 'typeMap' => self::TYPE_MAP]);
        return  ($sessionCounter and !empty($sessionCounter->count)) ? $sessionCounter->count : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function otp(string $sessionId): int
    {
        $session = $this->collection($this->config['collection_session'])
                        ->findOne(['id' => $sessionId], ['projection' => ['otp' => 1], 'typeMap' => self::TYPE_MAP]);
        return  ($session and !empty($session->otp)) ? $session->otp :  0;
    }

    /**
     * {@inheritdoc}
     */
    public function otpCheckIncrement(string $sessionId): self
    {
        $this->collection($this->config['collection_session'])
             ->updateOne(['id' => $sessionId], ['$inc' => ['otp_check_count' => 1]]);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function otpCheckCounter(string $sessionId): int
    {
        $session = $this->collection($this->config['collection_session'])
                        ->findOne(['id' => $sessionId], ['projection' => ['otp_check_count' => 1], 'typeMap' => self::TYPE_MAP]);
        return  ($session and !empty($session->otp_check_count)) ? $session->otp_check_count : 0;
    }
}
