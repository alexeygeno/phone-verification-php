<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Storage;

interface I
{
    public function sessionUp(string $sessionId, int $otp, int $sessionExpSecs, int $sessionCounterExpSecs): I;
    public function sessionDown(string $sessionId): I;
    public function sessionCounter(string $sessionId): int;
    public function otp(string $sessionId): int;
    public function otpCheckIncrement(string $sessionId): I;
    public function otpCheckCounter(string $sessionId): int;
}
