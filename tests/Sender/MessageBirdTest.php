<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Sender;

use AlexGeno\PhoneVerification\Sender\MessageBird;
use PHPUnit\Framework\TestCase;

/**
 * Test the MessageBird sender
 */
final class MessageBirdTest extends TestCase
{
    /**
     * Api client data data provider
     *
     * @return string[][]
     */
    public function apiClientData(): array
    {
        return [
            // Values: from, to, text
            'api_client_data_1' => ['MySuperBrand', '+442077206312', 'Your code is 34566'],
            'api_client_data_2' => ['MyGreatBrand', '+15417543010', 'Your code is 45456']
        ];
    }

    /**
     * Checks if the invoke method is called as expected
     *
     * @dataProvider apiClientData
     * @param string $from
     * @param string $to
     * @param string $text
     * @return void
     */
    public function testInvoke(string $from, string $to, string $text): void
    {
        $clientMock = $this->getMockBuilder(\MessageBird\Client::class)->disableOriginalConstructor()->getMock();

        $resourceMessagesMock = $this->createMock(\MessageBird\Resources\Messages::class);

        $objectMessageMock = $this->createMock(\MessageBird\Objects\Message::class);
        $objectMessageMock->originator = $from;
        $objectMessageMock->recipients = [$to];
        $objectMessageMock->body = $text;

        // Mocking $this->client->messages inside MessageBird::invoke
        $clientMock->messages = $resourceMessagesMock;

        // Clone to compare 2 different objects by their data.
        // Since objects always are passed by reference without cloning we can't see if
        // \MessageBird\Objects\Message will be built incorrectly inside MessageBird::invoke
        $resourceMessagesMock->expects($this->once())
                             ->method('create')
                             ->with(clone $objectMessageMock);

        $sender = new MessageBird($clientMock, $objectMessageMock);
        $sender->invoke($to, $text);
    }
}
