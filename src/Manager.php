<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification;


use AlexGeno\PhoneVerification\Exception\Otp;
use AlexGeno\PhoneVerification\Exception\RateLimit;
use AlexGeno\PhoneVerification\Sender\I as ISender;
use AlexGeno\PhoneVerification\Storage\I as IStorage;

/**
 * Manager class is an entry point to the package
 */
class Manager
{
    protected array $config;
    protected IStorage $storage;
    protected ISender $sender;
    protected int $otp;
    protected int $otpMin;
    protected int $otpMax;

    /**
     * Manager constructor
     *
     * @param IStorage $storage
     * @param array    $config  Every param has a default value and could be replaced
     *           [
     *               'otp' => [
     *                   'length' => 4,
     *                   'message' =>  (fn($otp) => sprintf('Your code is %d', $otp)),
     *                   'message_incorrect' =>  fn($otp) => 'Code is incorrect',
     *                   'message_expired' =>  fn($periodSecs, $otp) => sprintf('Code is expired. It is valid for %d minutes.', $periodSecs / 60)
     *               ],
     *               'rate_limits' => [
     *                   'initiate' => [
     *                       'period_secs' => 86400, 'count' => 10,
     *                       'message' => fn($phone, $periodSecs, $count) => (sprintf('You can send only %d sms in %d hours.', $count, $periodSecs / 60 / 60))
     *                   ],
     *                   'complete' => [
     *                       'period_secs' => 300, 'count' => 5,
     *                       'message' => fn($phone, $periodSecs, $count) => (sprintf('You have been using an incorrect code %d times in %d minutes.', $count, $periodSecs / 60))
     *               ]
     *            ]
     */
    public function __construct(IStorage $storage, array $config = [])
    {
        $this->config = array_replace_recursive([
            'otp' => [
                'length' => 4,
                'message' =>  fn($otp) => sprintf('Your code is %d', $otp),
                'message_incorrect' =>  fn($otp) => 'Code is incorrect',
                'message_expired' =>  fn($periodSecs, $otp) => sprintf('Code is expired. It is valid for %d minutes.', $periodSecs / 60)
            ],
            'rate_limits' => [
                'initiate' => [
                    // You can initiate confirmation 10 times per 24 hours!
                    'period_secs' => 86400, 'count' => 10,
                    'message' => fn($phone, $periodSecs, $count) => sprintf('You can send only %d sms in %d hours.', $count, $periodSecs / 60 / 60)
                ],
                'complete' => [
                    // You can complete confirmation 5 times per 5 minutes!
                    'period_secs' => 300, 'count' => 5,
                    'message' => fn($phone, $periodSecs, $count) => sprintf('You have been using an incorrect code %d times in %d minutes.', $count, $periodSecs / 60)
                    ]
            ]
        ], $config);

        $this->storage = $storage;

        $otpLength = (int)$this->config['otp']['length'];
        $this->otpMin = pow(10, $otpLength - 1);
        $this->otpMax = pow(10, $otpLength) - 1;
    }

    /**
     * Sets sender
     * Must be called before the initiate method
     *
     * @param ISender $sender
     * @return $this
     */
    public function sender(ISender $sender): Manager
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * Returns a generated otp
     *
     * @return integer
     */
    public function otp(): int
    {
        return $this->otp;
    }

    /**
     * Initiates the verification process by sending an otp to a phone.
     * Returns sender's API response
     *
     * @param string $phone
     * @return mixed
     * @throws Exception\RateLimit
     * @throws Exception
     */
    public function initiate(string $phone)
    {
        if (!isset($this->sender)) {
            throw new Exception('Sender is required to call Manager::initiate. Try to call Manager::sender before.');
        }
        $this->otp = rand($this->otpMin, $this->otpMax);
        $message = $this->config['otp']['message']($this->otp);

        $rateLimitInitiate = $this->config['rate_limits']['initiate'];
        if ($this->storage->sessionCounter($phone) >= (int)$rateLimitInitiate['count']) {
            throw new RateLimit($rateLimitInitiate['message']($phone, $rateLimitInitiate['period_secs'], $rateLimitInitiate['count']), RateLimit::CODE_INITIATE);
        }
        $this->storage->sessionUp($phone, $this->otp, $this->config['rate_limits']['complete']['period_secs'], $rateLimitInitiate['period_secs']);
        return $this->sender->invoke($phone, $message);
    }

    /**
     * Completes the verification process by checking if the otp is correct for the phone
     *
     * @param string  $phone
     * @param integer $otp
     * @return $this
     * @throws Otp
     * @throws RateLimit
     */
    public function complete(string $phone, int $otp): Manager
    {
        $rateLimit = $this->config['rate_limits']['complete'];

        if ($this->storage->otpCheckCounter($phone) >= (int)$rateLimit['count']) {
            throw new RateLimit($rateLimit['message']($phone, $rateLimit['period_secs'], $rateLimit['count']), RateLimit::CODE_COMPLETE);
        }

        $storedOtp = $this->storage->otp($phone);

        // Expired otp!
        if ($storedOtp === 0) {
            throw new Otp($this->config['otp']['message_expired']($rateLimit['period_secs'], $otp), Otp::CODE_EXPIRED);
        }
        // Incorrect otp!
        if ($storedOtp !== $otp) {
            $this->storage->otpCheckIncrement($phone);
            throw new Otp($this->config['otp']['message_incorrect']($otp), Otp::CODE_INCORRECT);
        }

        // Correct otp. So we tear down the session!
        $this->storage->sessionDown($phone);

        return $this;
    }
}
