<?php

namespace AlexGeno\PhoneVerification;


use AlexGeno\PhoneVerification\Exception\Otp;
use AlexGeno\PhoneVerification\Exception\RateLimit;

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
            'otp' => [
                'length' => 4,
                'message' =>  (fn($otp) => sprintf('Your code is %d', $otp)),
                'message_incorrect' =>  fn($otp) => 'Code is incorrect',
                'message_expired' =>  fn($otp) => 'Code is expired',
            ],
            'rate_limits' => [
                'initiate' => [
                    //you can initiate confirmation 10 times per 24 hours
                    'period_secs' => 86400, 'count' => 10,
                    'message' => (fn($phone, $periodSecs, $count) => (sprintf('You can send only %d sms per %d hours.',
                                        $count, $periodSecs/60/60)))
                ],
                'complete' => [
                    //you can complete confirmation 5 times per 5 minutes
                    'period_secs' => 300, 'count' => 5,
                    'message' => (fn($phone, $periodSecs, $count) => (sprintf('You have been using an incorrect code more than %d times per %d minutes',
                        $count, $periodSecs/60)))
                    ]
            ]
        ], $config);

        if(!($this->config['otp']['message'] instanceof \Closure)){
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

        $rateLimit = $this->config['rate_limits']['initiate'];
        if ($this->storage->sessionCounter($phone) >= (int)$rateLimit['count']) {
            throw new RateLimit($rateLimit['message']($phone, $rateLimit['period_secs'], $rateLimit['count']),RateLimit::CODE_INITIATE);
        }
        $this->storage->sessionUp($phone, $otp, $this->config['rate_limits']['complete']['period_secs'], $rateLimit['period_secs']);
        $this->sender->invoke($phone, $message);
        return $otp;
    }

    /**
     * @throws Exception\Otp
     * @throws Exception\RateLimit
     */
    public function complete(string $phone, int $otp): Manager
    {
        $rateLimit = $this->config['rate_limits']['complete'];

        if ($this->storage->otpCheckCounter($phone) > (int)$rateLimit['count']) {
            throw new RateLimit($rateLimit['message']($phone, $rateLimit['period_secs'], $rateLimit['count']), RateLimit::CODE_COMPLETE);
        }

        $storedOtp = $this->storage->otp($phone);


        if ($storedOtp === 0) {
            throw new Otp($this->config['otp']['message_expired']($otp), Otp::CODE_EXPIRED);
        }

        if ($storedOtp !== $otp) {
            $this->storage->otpCheckIncrement($phone);
            throw new Otp($this->config['otp']['message_incorrect']($otp), Otp::CODE_INCORRECT);
        }

        //correct otp
        $this->storage->sessionDown($phone);

        return $this;
    }
}

