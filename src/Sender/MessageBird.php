<?php

namespace AlexGeno\PhoneVerification\Sender;

use MessageBird\Client;
use MessageBird\Objects\Message;

/**
 * Class MessageBird
 * @see https://developers.messagebird.com/api/sms-messaging/#send-outbound-sms
 * @package AlexGeno\PhoneVerification\Sender
 */
class MessageBird implements I
{
    protected Client $client;
    protected Message $message;

    public function __construct(Client $client, Message $message)
    {
        $this->client = $client;
        $this->message = $message;
    }


    public function invoke(string $to, string $text)
    {
        $this->message->recipients = [$to];
        $this->message->body = $text;
        return $this->client->messages->create($this->message);
    }
}
