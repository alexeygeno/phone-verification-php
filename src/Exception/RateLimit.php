<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Exception;

use AlexGeno\PhoneVerification\Exception;

/**
 * Exception class that identifies violating amount restrictions during the verification process
 */
class RateLimit extends Exception
{
    // Possible codes(types) for the exception
    public const CODE_INITIATE = 10;
    public const CODE_COMPLETE = 20;
}
