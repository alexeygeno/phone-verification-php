<?php declare(strict_types=1);

use AlexGeno\PhoneVerification\Sender\Twilio;
use AlexGeno\PhoneVerification\Sender\Vonage;
use AlexGeno\PhoneVerification\Sender\MessageBird;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
/**
 * Class Sender
 * Sender factory - creates sender clients instances
 */
class Sender
{
    /**
     * Returns Twilio client instance
     * @return Twilio
     * @throws \Twilio\Exceptions\ConfigurationException
     */
    public function twilio(): Twilio
    {
        return new Twilio(new \Twilio\Rest\Client(getenv('TWILIO_ACCOUNT_SID'), getenv('TWILIO_AUTH_TOKEN')), ['from' => getenv('TWILIO_FROM')]);
    }

    /**
     * Returns Vonage client instance
     * @return Vonage
     */
    public function vonage(): Vonage
    {
        return new Vonage(new \Vonage\Client(new \Vonage\Client\Credentials\Basic(getenv('VONAGE_API_KEY'), getenv('VONAGE_API_SECRET'))), getenv('VONAGE_BRAND_NAME'));
    }

    /**
     * Returns MessageBird client instance
     * @return MessageBird
     */
    public function messageBird(): MessageBird
    {
        $message = new \MessageBird\Objects\Message();
        $message->originator = getenv('MESSAGEBIRD_ORIGINATOR');
        $message->datacoding = 'unicode';
        return new MessageBird(new \MessageBird\Client(getenv('MESSAGEBIRD_ACCESS_KEY')), $message);
    }
}
