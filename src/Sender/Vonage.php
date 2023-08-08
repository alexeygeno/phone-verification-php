<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerification\Sender;

use Vonage\Client;
use Vonage\SMS\Message\SMS;

/**
 * Vonage sender implementation
 */
class Vonage implements I
{
    protected Client $client;
    protected string $brandName;

    /**
     * Constructor
     * @link https://developer.vonage.com/en/messaging/sms/code-snippets/send-an-sms-with-unicode
     *
     * @param Client $client
     * @param string $brandName
     */
    public function __construct(Client $client, string $brandName)
    {
        $this->client = $client;
        $this->brandName = $brandName;
    }

    /**
     * {@inheritdoc}
     */
    public function invoke(string $to, string $text)
    {
        return $this->client->sms()->send(
            new SMS($to, $this->brandName, $text, 'unicode')
        );
    }
}
