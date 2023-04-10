<?php

namespace AlexGeno\PhoneVerification;


class Manager
{
    protected array $config;
    protected \AlexGeno\PhoneVerification\Storage\I $storage;
    protected \AlexGeno\PhoneVerification\Sender\I $sender;
    protected int $otpMin;
    protected int $otpMax;

    /**
     * Manager constructor.
     * @param Storage\I $storage
     * @param Sender\I $sender
     * @param array $config
     * @throws Exception
     */
    public function __construct(\AlexGeno\PhoneVerification\Storage\I $storage, \AlexGeno\PhoneVerification\Sender\I $sender, array $config = [])
    {
        $this->config = array_replace_recursive([
            'otp' => ['length' => 4, 'message' =>  (fn($otp) => sprintf('Your code is %d', $otp))],
            'rate_limits' => [
                'initiate' => ['period_secs' => 86400, 'count' => 10],  //you can initiate confirmation 10 times per 24 hours
                'complete' => ['period_secs' => 300, 'count' => 5]  //you can complete confirmation 5 times per 5 minutes
            ]
        ], $config);

        $callback = $this->config['otp']['message'];
        if(!($callback instanceof \Closure)){
            throw new \AlexGeno\PhoneVerification\Exception('Check the config item "otp.message". It must be an anonymous func.');
        }

        $this->storage = $storage;
        $this->sender = $sender;

        $otpLength = (int)$this->config['otp']['length'];
        $this->otpMin = pow(10, $otpLength - 1);
        $this->otpMax = pow(10, $otpLength) - 1;
    }

    /**
     * @param $phone
     * @return int
     * @throws Exception\RateLimit
     */
    public function start($phone): int
    {
        $otp = rand($this->otpMin, $this->otpMax);
        $message = $this->config['otp']['message']($otp);

        if ($this->storage->sessionCounter($phone) > (int)$this->config['rate_limits']['initiate']['count']) {
            throw new \AlexGeno\PhoneVerification\Exception\RateLimit('initiate', $this->config['rate_limits']['initiate']);
        }
        $this->storage->sessionUp($phone, $otp, $this->config['rate_limits']['complete']['period_secs'], $this->config['rate_limits']['initiate']['period_secs']);
        $this->sender->invoke($phone, $message);
        return $otp;
    }

    /**
     * @throws Exception\Otp
     * @throws Exception\RateLimit
     */
    public function complete(string $phone, int $otp): Manager
    {

        if ($this->storage->otpCheckCounter($phone) > (int)$this->config['rate_limits']['complete']['count']) {
            throw new \AlexGeno\PhoneVerification\Exception\RateLimit('complete', $this->config['rate_limits']['complete']);
        }

        $storedOtp = $this->storage->otp($phone);


        if ($storedOtp === 0) {
            //TODO: otp expired
            //throw new \AlexGeno\PhoneVerification\Exception\Otp($phone, $otp);
        }


        if ($storedOtp !== $otp) {
            $this->storage->otpCheckIncrement($phone);
            throw new \AlexGeno\PhoneVerification\Exception\Otp($phone, $otp);
        }

        //correct otp
        $this->storage->sessionDown($phone);

        return $this;
    }
}

