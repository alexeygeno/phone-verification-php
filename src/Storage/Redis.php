<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Storage;

use Predis\Client;

/**
 * Redis storage implementation
 */
class Redis implements I
{
    protected Client $client;

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
     * Returns the session key
     *
     * @param string $sessionId
     * @return string
     */
    protected function sessionKey(string $sessionId): string
    {
        return "{$this->config['prefix']}:{$this->config['session_key']}:{$sessionId}";
    }

    /**
     * Returns the session counter key
     *
     * @param string $sessionId
     * @return string
     */
    protected function sessionCounterKey(string $sessionId): string
    {
        return "{$this->config['prefix']}:{$this->config['session_counter_key']}:{$sessionId}";
    }

    /**
     * {@inheritdoc}
     */
    public function sessionUp(string $sessionId, int $otp, int $sessionExpSecs, int $sessionCounterExpSecs): self
    {
        // Session
        $this->client->hmset($this->sessionKey($sessionId), ['otp' => $otp, 'otp_check_count' => 0 ]);
        $this->client->expire($this->sessionKey($sessionId), $sessionExpSecs);

        // Session counter
        $this->client->incr($this->sessionCounterKey($sessionId));
        $this->client->expire($this->sessionCounterKey($sessionId), $sessionCounterExpSecs, 'NX');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function sessionDown(string $sessionId): self
    {
        $this->client->del($this->sessionKey($sessionId));
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function sessionCounter(string $sessionId): int
    {
        return (int)$this->client->get($this->sessionCounterKey($sessionId));
    }

    /**
     * {@inheritdoc}
     */
    public function otp(string $sessionId): int
    {
        return  (int)$this->client->hget($this->sessionKey($sessionId), 'otp');
    }

    /**
     * {@inheritdoc}
     */
    public function otpCheckIncrement(string $sessionId): self
    {
        $this->client->hincrby($this->sessionKey($sessionId), 'otp_check_count', 1);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function otpCheckCounter(string $sessionId): int
    {
        return (int)$this->client->hget($this->sessionKey($sessionId), 'otp_check_count');
    }
}
