<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Exception;

class Otp extends \Exception
{
    const CODE_INCORRECT = 1;
    const CODE_EXPIRED = 2;

    public function __construct( string $message = '', $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}