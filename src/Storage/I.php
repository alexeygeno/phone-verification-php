<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Storage;

/**
 * Interface for implementing a storage
 *
 * Session - an array containing verification info
 * Session counter - the number of times the session was recreated
 * OTP - One-time password
 * OTP check counter - the number of executed attempts to verify a session
 */
interface I
{
    /**
     * Creates a session and increments its counter
     *
     * @param string  $sessionId
     * @param integer $otp
     * @param integer $sessionExpSecs
     * @param integer $sessionCounterExpSecs
     * @return I
     */
    public function sessionUp(string $sessionId, int $otp, int $sessionExpSecs, int $sessionCounterExpSecs): self;

    /**
     * Drops the session
     *
     * @param string $sessionId
     * @return I
     */
    public function sessionDown(string $sessionId): self;

    /**
     * Returns the number of times the session was recreated
     *
     * @param string $sessionId
     * @return integer
     */
    public function sessionCounter(string $sessionId): int;

    /**
     * Returns the one-time password for the session
     *
     * @param string $sessionId
     * @return integer
     */
    public function otp(string $sessionId): int;

    /**
     * Increments the number of attempts to verify the session
     *
     * @param string $sessionId
     * @return I
     */
    public function otpCheckIncrement(string $sessionId): self;

    /**
     * Returns the number of attempts to verify the session
     *
     * @param string $sessionId
     * @return integer
     */
    public function otpCheckCounter(string $sessionId): int;
}
