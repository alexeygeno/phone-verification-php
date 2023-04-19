<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Sender;

use AlexGeno\PhoneVerification\Sender\Twilio;
use PHPUnit\Framework\TestCase;

/**
 * Class to test the Twilio sender
 */
final class TwilioTest extends TestCase
{
    /**
     * Api client data data provider
     *
     * @return string[][]
     */
    public function apiClientData(): array
    {
        // Values: from, to, text
        return [
            'api_client_data_1' => ['+380935258272', '+442077206312', 'Your code is 23234'],
            'api_client_data_2' => ['380935258272', '5417543010', 'Your code is 56895']
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
    public function testInvoke(string $from, string $to, string $text)
    {
        $clientMock = $this->getMockBuilder(\Twilio\Rest\Client::class)->disableOriginalConstructor()->getMock();

        $messageListMock = $this->createMock(\Twilio\Rest\Api\V2010\Account\MessageList::class);

        $messageListMock->expects($this->once())
                        ->method('create')
                        ->with($to, ['from' => $from,'body' => $text]);

        $clientMock->expects($this->once())
                    ->method('__get')
                    ->with('messages')
                    ->willReturn($messageListMock);

        $sender = new Twilio($clientMock, ['from' => $from]);
        $sender->invoke($to, $text);
    }
}
