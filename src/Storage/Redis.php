<?php

namespace AlexGeno\PhoneVerification\Storage;

use Predis\Client;

class Redis implements I
{
    protected Client $redis;
    protected array $config;

    /**
     *
     * @param Client $redis
     * @param array $config
     */
    public function __construct(Client $redis, array $config = [])
    {
        $this->redis = $redis;
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
        $this->redis->multi();

        //session
        $this->redis->hmset($this->sessionKey($sessionId), ['otp' => $otp, 'attempts' => 0 ]);
        $this->redis->expire($this->sessionKey($sessionId), $sessionExpSecs, 'GT');

        //session counter
        $this->redis->incr($this->sessionCounterKey($sessionId));
        $this->redis->expire($this->sessionCounterKey($sessionId), $sessionCounterExpSecs, 'NX');

        $this->redis->exec();
        return $this;
    }
    public function sessionDown(string $sessionId): I
    {
        $this->redis->del($this->sessionKey($sessionId));
        return $this;
    }

    public function otp(string $sessionId): int
    {
        $otp = $this->redis->hget($this->sessionKey($sessionId), 'otp');
        return  $otp ?? 0;
    }

    public function otpCheckIncrement(string $sessionId): I
    {
        $this->redis->hincrby($this->sessionKey($sessionId), 'attempts', 1);
        return $this;
    }
    public function otpCheckCounter(string $sessionId): int
    {
        return (int)$this->redis->hget($this->sessionKey($sessionId), 'attempts');
    }
    public function sessionCounter(string $sessionId): int
    {
        return (int)$this->redis->get($this->sessionCounterKey($sessionId));
    }
}
