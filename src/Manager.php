<?php

namespace AlexGeno\PhoneVerification;

use AlexGeno\PhoneVerification\Exception\MaxAttemptsExceeded;
use AlexGeno\PhoneVerification\Storage\I;

class Manager
{
    protected array $config;
    protected I $storage;
    protected Provider\I $provider;
    protected int $otpMin;
    protected int $otpMax;
    public function __construct(I $storage, Provider\I $provider, array $config = array())
    {
        $this->config = array_merge(array(
            'otp_length' => 4,
            'otp_exp_period' => 300,
            'max_attempts' => 5,
            'message' => "Your code is %d",
            'storage_key_prefix' => 'pv:1'
        ), $config);
        $this->storage = $storage;
        $this->provider = $provider;

        $otpLength = (int)$this->config['otp_length'];
        $this->otpMin = pow(10, $otpLength - 1);
        $this->otpMax = pow(10, $otpLength) - 1;
    }

    /**
     * @param $phone
     * @return int
     */
    public function start($phone): int
    {

        $otp = rand($this->otpMin, $this->otpMax);
        $message = sprintf($this->config['message'], $otp);
        $this->provider->sms($phone, $message);
        $this->storage->setupSession($phone, $otp, $this->config['otp_exp_period']);
        return $otp;
    }

    /**
     * @throws Exception\WrongOtp
     * @throws Exception\ExpiredOtp
     * @throws Exception\MaxAttemptsExceeded
     */
    public function complete(string $phone, int $otp): Manager
    {

        $attempts = $this->storage->attemptsCount($phone);
        $maxAttempts = (int)$this->config['max_attempts'];

        if ($attempts > $maxAttempts) {
            throw new MaxAttemptsExceeded($phone, $maxAttempts, $this->config['otp_exp_period']);
        }

        $storedOtp = $this->storage->otp($phone);

        if ($storedOtp === 0) {
            throw new \AlexGeno\PhoneVerification\Exception\ExpiredOtp($phone, $otp);
        }

        if ($storedOtp !== $otp) {
            $this->storage->incrementAttempts($phone);
            throw new \AlexGeno\PhoneVerification\Exception\WrongOtp($phone, $otp);
        }


        $this->storage->resetSession($phone);

        return $this;
    }
}
