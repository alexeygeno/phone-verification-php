<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Sender;

use AlexGeno\PhoneVerification\Sender\Vonage;
use PHPUnit\Framework\TestCase;

/**
 * Class to test the Vonage sender
 */
final class VonageTest extends TestCase
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
            'api_client_data_1' => ['+15417543010', '+442077206312', 'Your code is 23234'],
            'api_client_data_2' => ['15417543010', '5417543010', 'Your code is 56895']
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
        $clientMock = $this->getMockBuilder(\Vonage\Client::class)->disableOriginalConstructor()->getMock();

        $smsClientMock = $this->createMock(\Vonage\SMS\Client::class);

        $smsClientMock->expects($this->once())
                      ->method('send')
                      ->with(new \Vonage\SMS\Message\SMS($to, $from, $text, 'unicode'));

        $clientMock->expects($this->once())
                   ->method('__call')
                   ->with('sms')
                   ->willReturn($smsClientMock);

        $sender = new Vonage($clientMock, $from);
        $sender->invoke($to, $text);
    }
}
