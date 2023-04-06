<?php

namespace AlexGeno\PhoneVerification\Sender;

interface I
{
    public function invoke(string $to, string $text);
}
