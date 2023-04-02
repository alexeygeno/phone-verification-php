<?php

namespace AlexGeno\PhoneVerification\Storage;

use Predis\Client;

class Redis implements I
{
    private Client $redis;

    /**
     * Redis Storage!
     *
     * @param Client $redis
     */
    public function __construct(Client $redis)
    {
        $this->redis = $redis;
    }


    public function setupSession(string $phone, int $otp, int $expirationSecs, $reset = false): I
    {
        $this->redis->multi();
        if ($reset) {
            $this->resetSession($phone);
        }
        $this->redis->hsetnx("s:$phone", 'otp', $otp);
        $this->redis->hsetnx("s:$phone", 'attempts', 0);
        $this->redis->expire("s:$phone", $expirationSecs, 'NX');
        $this->redis->exec();
        return $this;
    }
    public function resetSession(string $phone): I
    {
        $this->redis->del("s:$phone");
        return $this;
    }

    public function otp(string $phone): int
    {
        $otp = $this->redis->hget("s:$phone", 'otp');
        return  $otp ?? 0;
    }

    public function incrementAttempts(string $phone): I
    {
        $this->redis->hincrby("s:$phone", 'attempts', 1);
        return $this;
    }
    public function attemptsCount(string $phone): int
    {
        return (int)$this->redis->hget("s:$phone", 'attempts');
    }
}
