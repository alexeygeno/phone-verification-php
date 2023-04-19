<?php declare(strict_types=1);

namespace AlexGeno\PhoneVerificationTests\Sender;

use PHPUnit\Framework\TestCase;
use Vonage\SMS\Message\SMS;

final class VonageTest extends TestCase
{
    public function clientData(): array
    {
        return [
             ['MySuperBrand', '+442077206312', 'Your code is 323323'], //from, to, text
             ['MyGreatBrand', '5417543010', 'Your code is 343434']
        ];
    }

    /**
     * @dataProvider clientData
     */
    public function testInvoke($from, $to, $text)
    {
        $clientMock = $this->getMockBuilder(\Vonage\Client::class)->disableOriginalConstructor()->getMock();

        $smsClientMock = $this->createMock(\Vonage\SMS\Client::class);

        $smsClientMock->expects($this->once())->method('send')->with(new \Vonage\SMS\Message\SMS($to, $from, $text, 'unicode'));

        $clientMock->expects($this->once())->method('__call')->with('sms')->willReturn($smsClientMock);

        $sender = new \AlexGeno\PhoneVerification\Sender\Vonage($clientMock, $from);
        $sender->invoke($to, $text);
    }
}
