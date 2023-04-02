<?php

namespace AlexGeno\PhoneVerification\Provider;

interface I
{
    public function sms($phone, $message): I;

    //public function call($phone);
}
