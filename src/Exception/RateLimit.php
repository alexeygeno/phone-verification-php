<?php

namespace AlexGeno\PhoneVerification\Exception;

use AlexGeno\PhoneVerification\Exception;

class RateLimit extends Exception
{
    protected string $type;
    protected array $limits;

    public function __construct(string $type, $limits, string $message = '', $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->type = $type;
        $this->limits = $limits;
    }

    public function limits(): array
    {
        return $this->limits;
    }

    public function type(): string
    {
        return $this->type;
    }
}
