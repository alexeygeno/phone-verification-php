<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Sender;

use Twilio\Rest\Client;

/**
 * Twilio sender implementation
 */
class Twilio implements I
{
    protected Client $client;
    /**
     * @var array<mixed>
     */
    protected array $options;

    /**
     * Constructor
     * @link https://www.twilio.com/docs/sms/quickstart/php
     * @link https://www.twilio.com/docs/sms/api/message-resource
     *
     * @param Client       $client
     * @param array<mixed> $options
     */
    public function __construct(Client $client, array $options)
    {
        $this->client = $client;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function invoke(string $to, string $text)
    {
        return
            $this->client->messages->create(
                $to,
                array_merge($this->options, ['body' => $text])
            );
    }
}
