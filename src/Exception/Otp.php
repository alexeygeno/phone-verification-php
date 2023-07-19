<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Exception;

use AlexGeno\PhoneVerification\Exception;

/**
 * Exception class that identifies OTP related errors
 */
class Otp extends Exception
{
    // Possible codes(types) for the exception
    public const CODE_INCORRECT = 1;
    public const CODE_EXPIRED = 2;
}
