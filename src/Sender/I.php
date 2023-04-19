<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Sender;

/**
 * Interface to implement a sender
 */
interface I
{
    /**
     * Performs sending
     * Returns API response
     *
     * @param string $to
     * @param string $text
     * @return mixed
     */
    public function invoke(string $to, string $text);
}
