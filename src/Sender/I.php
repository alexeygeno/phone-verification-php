<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Sender;

/**
 * Interface for implementing a sender
 */
interface I
{
    /**
     * Performs the sending operation and returns the API response
     * Returns API response
     *
     * @param string $to
     * @param string $text
     * @return mixed
     */
    public function invoke(string $to, string $text);
}
