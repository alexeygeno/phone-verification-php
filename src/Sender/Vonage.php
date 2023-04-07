<?php

namespace AlexGeno\PhoneVerification\Sender;

use Vonage\Client;
use Vonage\SMS\Message\SMS;

/**
 * Class Vonage
 * @see https://developer.vonage.com/en/messaging/sms/code-snippets/send-an-sms-with-unicode
 * @package AlexGeno\PhoneVerification\Sender
 */
class Vonage implements I
{
    protected Client $client;
    protected string $brandName;

    public function __construct(Client $client, string $brandName)
    {
        $this->client = $client;
        $this->brandName = $brandName;
    }

    public function invoke(string $to, string $text)
    {
        return $this->client->sms()->send(
            new SMS($to, $this->brandName, $text, 'unicode')
        );
    }
}
