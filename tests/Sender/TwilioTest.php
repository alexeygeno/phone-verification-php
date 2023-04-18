<?php

declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Sender;

use PHPUnit\Framework\TestCase;

final class TwilioTest extends TestCase
{
    public function clientData(): array
    {
        return [
             ['+380935258272', '+442077206312', 'Your code is 23234'], //from, to, text
             ['380935258272', '5417543010', 'Your code is 56895']
        ];
    }

    /**
     * @dataProvider clientData
     */
    public function testInvoke($from, $to, $text)
    {
        $clientMock = $this->getMockBuilder(\Twilio\Rest\Client::class)->disableOriginalConstructor()->getMock();

        $messageListMock = $this->createMock(\Twilio\Rest\Api\V2010\Account\MessageList::class);

        $messageListMock->expects($this->once())->method('create')->with($to, ['from' => $from,'body' => $text]);

        $clientMock->expects($this->once())->method('__get')->with('messages')->willReturn($messageListMock);

        $sender = new \AlexGeno\PhoneVerification\Sender\Twilio($clientMock, ['from' => $from]);
        $sender->invoke($to, $text);
    }
}
