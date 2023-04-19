<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Sender;

interface I
{
    public function invoke(string $to, string $text);
}
