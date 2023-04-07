<?php

namespace AlexGeno\PhoneVerification\Storage;

interface I
{
    public function setupSession(string $id, int $otp, int $expirationSecs/*, $reset = false*/): I;
    public function resetSession(string $id): I;
    public function otp(string $sessionId): int;
    public function incrementAttempts(string $sessionId): I;
    public function attemptsCount(string $sessionId): int;
}
