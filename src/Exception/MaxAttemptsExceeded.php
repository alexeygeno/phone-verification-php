<?php

namespace AlexGeno\PhoneVerification\Exception;

class MaxAttemptsExceeded extends \Exception
{
    protected string $phone;
    protected int $maxAttempts;
    protected int $availablePeriod;

    public function __construct(string $phone, int $maxAttempts, int $availablePeriod, string $message = '')
    {
        parent::__construct($message);
        $this->phone = $phone;
        $this->maxAttempts = $maxAttempts;
        $this->availablePeriod = $availablePeriod;
    }

    public function phone(): string
    {
        return $this->phone;
    }

    /**
     * @return int Period in seconds in which you can attempt again
     */
    public function availablePeriod(): int
    {
        return $this->availablePeriod;
    }

    public function maxAttempts(): int
    {
        return $this->maxAttempts;
    }
}
