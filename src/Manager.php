<?php

namespace AlexGeno\PhoneVerification;

use AlexGeno\PhoneVerification\Exception\MaxAttemptsExceeded;

class Manager
{
    protected array $config;
    protected \AlexGeno\PhoneVerification\Storage\I $storage;
    protected \AlexGeno\PhoneVerification\Sender\I $sender;
    protected int $otpMin;
    protected int $otpMax;
    public function __construct(\AlexGeno\PhoneVerification\Storage\I $storage, \AlexGeno\PhoneVerification\Sender\I $sender, array $config = array())
    {
        $this->config = array_replace_recursive(array(
            'otp_length' => 4,
            'otp_exp_period' => 300, //5 min
            'session_exp_period' => 3600, //1 hour
            //not implemented yet
//            'rate_limits' => [
//                'initiate' => ['period_secs' => 86400, 'count' => 10],  //you can initiate confirmation 10 times per 24 hours
//                'complete' => ['period_secs' => 300, 'count' => 5]  //you can complete confirmation 5 times per 5 minutes
//            ],
            'max_attempts' => 5,
            'message' => "Your code is %d", //will be processed by sprintf
        ), $config);
        $this->storage = $storage;
        $this->sender = $sender;

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
        $this->storage->sessionUp($phone, $otp, $this->config['otp_exp_period'], $this->config['session_exp_period']);
        $this->sender->invoke($phone, $message);
        return $otp;
    }

    /**
     * @throws Exception\WrongOtp
     * @throws Exception\ExpiredOtp
     * @throws Exception\MaxAttemptsExceeded
     */
    public function complete(string $phone, int $otp): Manager
    {

        $attempts = $this->storage->otpCheckCounter($phone);
        $maxAttempts = (int)$this->config['max_attempts'];

        if ($attempts > $maxAttempts) {
            throw new MaxAttemptsExceeded($phone, $maxAttempts, $this->config['otp_exp_period']);
        }

        $storedOtp = $this->storage->otp($phone);

        if ($storedOtp === 0) {
            throw new \AlexGeno\PhoneVerification\Exception\ExpiredOtp($phone, $otp);
        }

        if ($storedOtp !== $otp) {
            $this->storage->otpCheckIncrement($phone);
            throw new \AlexGeno\PhoneVerification\Exception\WrongOtp($phone, $otp);
        }


        $this->storage->sessionDown($phone);

        return $this;
    }
}
