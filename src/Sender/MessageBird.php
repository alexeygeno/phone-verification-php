<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Sender;

use MessageBird\Client;
use MessageBird\Objects\Message;

/**
 * MessageBird sender implementation
 */
class MessageBird implements I
{
    protected Client $client;
    protected Message $message;

    /**
     * MessageBird constructor
     * @link https://developers.messagebird.com/api/sms-messaging/#send-outbound-sms
     *
     * @param Client  $client
     * @param Message $message
     */
    public function __construct(Client $client, Message $message)
    {
        $this->client = $client;
        $this->message = $message;
    }

    /**
     * Performs sending
     * Returns API response
     *
     * @param string $to
     * @param string $text
     * @return mixed
     * @throws \JsonException
     * @throws \MessageBird\Exceptions\AuthenticateException
     * @throws \MessageBird\Exceptions\BalanceException
     * @throws \MessageBird\Exceptions\HttpException
     */
    public function invoke(string $to, string $text)
    {
        $this->message->recipients = [$to];
        $this->message->body = $text;
        return $this->client->messages->create($this->message);
    }
}
