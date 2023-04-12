<?php

namespace AlexGeno\PhoneVerification\Storage;

use Predis\Client;

class Redis implements I
{
    protected Client $client;
    protected array $config;

    /**
     *
     * @param Client $redis
     * @param array $config
     */
    public function __construct(Client $client, array $config = [])
    {
        $this->client = $client;
        $this->config = array_replace(['prefix' => 'pvs:1', 'session_key'=>'session',  'session_counter_key'=>'session_counter'], $config);
    }

    protected function sessionKey(string $sessionId): string
    {
        return "{$this->config['prefix']}:{$this->config['session_key']}:{$sessionId}";
    }

    protected function sessionCounterKey(string $sessionId): string
    {
        return "{$this->config['prefix']}:{$this->config['session_counter_key']}:{$sessionId}";
    }

    public function sessionUp(string $sessionId, int $otp, int $sessionExpSecs, int $sessionCounterExpSecs): I
    {
        $this->client->multi();

        //session
        $this->client->hmset($this->sessionKey($sessionId), ['otp' => $otp, 'otp_check_count' => 0 ]);
        $this->client->expire($this->sessionKey($sessionId), $sessionExpSecs, 'GT');

        //session counter
        $this->client->incr($this->sessionCounterKey($sessionId));
        $this->client->expire($this->sessionCounterKey($sessionId), $sessionCounterExpSecs, 'NX');

        $this->client->exec();
        return $this;
    }
    public function sessionDown(string $sessionId): I
    {
        $this->client->del($this->sessionKey($sessionId));
        return $this;
    }

    public function otp(string $sessionId): int
    {
        $otp = $this->client->hget($this->sessionKey($sessionId), 'otp');
        return  $otp ?? 0;
    }

    public function otpCheckIncrement(string $sessionId): I
    {
        $this->client->hincrby($this->sessionKey($sessionId), 'otp_check_count', 1);
        return $this;
    }
    public function otpCheckCounter(string $sessionId): int
    {
        return (int)$this->client->hget($this->sessionKey($sessionId), 'otp_check_count');
    }
    public function sessionCounter(string $sessionId): int
    {
        return (int)$this->client->get($this->sessionCounterKey($sessionId));
    }
}
