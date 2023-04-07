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
        $this->config = array_merge(['prefix' => 'pvs:1'], $config);
    }

    private function key(string $key): string
    {
        return $this->config['prefix'] . $key;
    }


    public function setupSession(string $id, int $otp, int $expirationSecs/*, $reset = false*/): I
    {
        $this->redis->multi();
//        if ($reset) {
//            $this->resetSession($phone);
//        }
        $this->redis->hset($this->key("s:$id"), 'otp', $otp);
        $this->redis->hset($this->key("s:$id"), 'attempts', 0);
        $this->redis->expire($this->key("s:$id"), $expirationSecs, 'GT');
        $this->redis->exec();
        return $this;
    }
    public function resetSession(string $id): I
    {
        $this->redis->del($this->key("s:$id"));
        return $this;
    }

    public function otp(string $sessionId): int
    {
        $otp = $this->redis->hget($this->key("s:$sessionId"), 'otp');
        return  $otp ?? 0;
    }

    public function incrementAttempts(string $sessionId): I
    {
        $this->redis->hincrby($this->key("s:$sessionId"), 'attempts', 1);
        return $this;
    }
    public function attemptsCount(string $sessionId): int
    {
        return (int)$this->redis->hget($this->key("s:$sessionId"), 'attempts');
    }
}
