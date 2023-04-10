<?php

namespace AlexGeno\PhoneVerification\Exception;

class Otp extends \Exception
{
    protected string $phone;
    protected int $otp;

    public function __construct(string $phone, int $otp, string $message = '')
    {
        parent::__construct($message);
        $this->phone = $phone;
        $this->otp = $otp;
    }

    public function otp(): int
    {
        return $this->otp;
    }

    public function phone(): string
    {
        return $this->phone;
    }
}
