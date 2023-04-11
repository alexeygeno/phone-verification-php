<?php

namespace AlexGeno\PhoneVerification\Exception;

use AlexGeno\PhoneVerification\Exception;

class RateLimit extends Exception
{
    const CODE_INITIATE = 10;
    const CODE_COMPLETE = 20;

    public function __construct(string $message = '', $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
