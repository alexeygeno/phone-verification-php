<?php

namespace AlexGeno\PhoneVerification\Storage;

interface I
{
    public function setupSession(string $phone, int $otp, int $expirationSecs, $reset = false):I;
    public function resetSession(string $phone):I;
//    public function sessionExist(string $phone):bool;
    public function otp(string $phone):int;
    public function incrementAttempts(string $phone):I;
    public function attemptsCount(string $phone):int;
}