<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Sender;

use Twilio\Rest\Client;

/**
 * Twilio sender implementation
 */
class Twilio implements I
{
    protected Client $client;
    protected array $options;

    /**
     * Twilio constructor
     * @link https://www.twilio.com/docs/sms/quickstart/php
     * @link https://www.twilio.com/docs/sms/api/message-resource
     *
     * @param Client $client
     * @param array  $options
     */
    public function __construct(Client $client, array $options)
    {
        $this->client = $client;
        $this->options = $options;
    }

    /**
     * Performs sending
     * Returns API response
     *
     * @param string $to
     * @param string $text
     * @return mixed|\Twilio\Rest\Api\V2010\Account\MessageInstance
     * @throws \Twilio\Exceptions\TwilioException
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
