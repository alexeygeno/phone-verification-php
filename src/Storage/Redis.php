<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Storage;

use Predis\Client;

/**
 * MongoDb storage implementation
 */
class Redis implements I
{
    protected Client $client;
    protected array $config;

    /**
     * Redis constructor
     *
     * @param Client $client
     * @param array  $config
     * Every param has a default value and could be replaced
     * [
     *     'prefix' => 'pv:1',
     *     'session_key' => 'session',
     *     'session_counter_key' => 'session_counter'
     * ]
     */
    public function __construct(Client $client, array $config = [])
    {
        $this->client = $client;
        $this->config = array_replace(['prefix' => 'pv:1', 'session_key' => 'session',
                                       'session_counter_key' => 'session_counter'], $config);
    }

    /**
     * Returns session full key
     *
     * @param string $sessionId
     * @return string
     */
    protected function sessionKey(string $sessionId): string
    {
        return "{$this->config['prefix']}:{$this->config['session_key']}:{$sessionId}";
    }

    /**
     * Returns session counter full key
     *
     * @param string $sessionId
     * @return string
     */
    protected function sessionCounterKey(string $sessionId): string
    {
        return "{$this->config['prefix']}:{$this->config['session_counter_key']}:{$sessionId}";
    }

    /**
     * Creates session and increments its counter
     *
     * @param string  $sessionId
     * @param integer $otp
     * @param integer $sessionExpSecs
     * @param integer $sessionCounterExpSecs
     * @return $this
     */
    public function sessionUp(string $sessionId, int $otp, int $sessionExpSecs, int $sessionCounterExpSecs): Redis
    {
        // Session
        $this->client->hmset($this->sessionKey($sessionId), ['otp' => $otp, 'otp_check_count' => 0 ]);
        $this->client->expire($this->sessionKey($sessionId), $sessionExpSecs);

        // Session counter
        $this->client->incr($this->sessionCounterKey($sessionId));
        $this->client->expire($this->sessionCounterKey($sessionId), $sessionCounterExpSecs, 'NX');

        /*
            TODO: make the transaction execution optional via config param $atomicity
            @link https://github.com/predis/predis#transactions
            $this->client->transaction(function($transaction) use ($sessionId, $otp, $sessionExpSecs, $sessionCounterExpSecs){
            // Session
            $transaction->hmset($this->sessionKey($sessionId), ['otp' => $otp, 'otp_check_count' => 0]);
            $transaction->expire($this->sessionKey($sessionId), $sessionExpSecs);

            // Session counter
            $transaction->incr($this->sessionCounterKey($sessionId));
            $transaction->expire($this->sessionCounterKey($sessionId), $sessionCounterExpSecs, 'NX');
            });
        */

        return $this;
    }

    /**
     * Drops session by its id
     *
     * @param string $sessionId
     * @return $this
     */
    public function sessionDown(string $sessionId): Redis
    {
        $this->client->del($this->sessionKey($sessionId));
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
        return (int)$this->client->get($this->sessionCounterKey($sessionId));
    }

    /**
     * Returns session otp
     *
     * @param string $sessionId
     * @return integer
     */
    public function otp(string $sessionId): int
    {
        return  (int)$this->client->hget($this->sessionKey($sessionId), 'otp');
    }

    /**
     * Increments the amount of otp checks for the session
     *
     * @param string $sessionId
     * @return $this
     */
    public function otpCheckIncrement(string $sessionId): Redis
    {
        $this->client->hincrby($this->sessionKey($sessionId), 'otp_check_count', 1);
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
        return (int)$this->client->hget($this->sessionKey($sessionId), 'otp_check_count');
    }
}
