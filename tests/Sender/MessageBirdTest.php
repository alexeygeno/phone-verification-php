<?php

declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Sender;

use PHPUnit\Framework\TestCase;

final class MessageBirdTest extends TestCase
{
    public function clientData(): array
    {
        return [
             ['MySuperBrand', '+442077206312', 'Your code is 34566'], //from, to, text
             ['MyGreatBrand', '+15417543010', 'Your code is 45456']
        ];
    }

    /**
     * @dataProvider clientData
     */
    public function testInvoke($from, $to, $text)
    {
        $clientMock = $this->getMockBuilder(\MessageBird\Client::class)->disableOriginalConstructor()->getMock();

        $messagesMock = $this->createMock(\MessageBird\Resources\Messages::class);

        $messageMock = $this->createMock(\MessageBird\Objects\Message::class);
        $messageMock->originator = $from;
        $messageMock->recipients = [$to];
        $messageMock->body = $text;

        //Mocking $this->client->messages inside MessageBird::invoke
        $clientMock->messages = $messagesMock;

        //need clone for having 2 different objects for comparison
        $messagesMock->expects($this->once())->method('create')->with(clone $messageMock);

        $sender = new \AlexGeno\PhoneVerification\Sender\MessageBird($clientMock, $messageMock);
        $sender->invoke($to, $text);
    }
}
