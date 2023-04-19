<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Sender;

use Twilio\Rest\Client;

/**
 * Class Twilio
 * @see https://www.twilio.com/docs/sms/quickstart/php
 * @see https://www.twilio.com/docs/sms/api/message-resource
 * @package AlexGeno\PhoneVerification\Sender
 */
class Twilio implements I
{
    protected Client $client;
    protected array $options;

    public function __construct(Client $client, array $options)
    {
        $this->client = $client;
        $this->options = $options;
    }

    public function invoke(string $to, string $text)
    {
        return
            $this->client->messages->create(
                $to,
                array_merge($this->options, ['body' => $text])
            );
    }
}
