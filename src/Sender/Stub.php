<?php

namespace AlexGeno\PhoneVerification\Sender;

class Stub implements I
{
    public function invoke(string $to, string $text):bool
    {
        return true;
    }
}
