<?php

namespace AlexGeno\PhoneVerification\Provider;

class Stub implements I
{
    public function sms($phone, $message):I{
        return $this;
    }
}