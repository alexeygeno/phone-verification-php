<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Storage;

/**
 * Interface to implement a storage
 */
interface I
{
    /**
     * Creates session and increments its counter
     *
     * @param string  $sessionId
     * @param integer $otp
     * @param integer $sessionExpSecs
     * @param integer $sessionCounterExpSecs
     * @return I
     */
    public function sessionUp(string $sessionId, int $otp, int $sessionExpSecs, int $sessionCounterExpSecs): self;

    /**
     * Drops session by its id
     *
     * @param string $sessionId
     * @return I
     */
    public function sessionDown(string $sessionId): self;

    /**
     * Returns the amount of recreated sessions
     *
     * @param string $sessionId
     * @return integer
     */
    public function sessionCounter(string $sessionId): int;

    /**
     * Returns session otp
     *
     * @param string $sessionId
     * @return integer
     */
    public function otp(string $sessionId): int;

    /**
     * Increments the amount of otp checks for the session
     *
     * @param string $sessionId
     * @return I
     */
    public function otpCheckIncrement(string $sessionId): self;

    /**
     * Returns the amount of otp checks for the session
     *
     * @param string $sessionId
     * @return integer
     */
    public function otpCheckCounter(string $sessionId): int;
}
