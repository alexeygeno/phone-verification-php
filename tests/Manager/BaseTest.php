<?php declare(strict_types=1);
namespace AlexGeno\PhoneVerificationTests\Manager;

use AlexGeno\PhoneVerification\Sender\Twilio;
use AlexGeno\PhoneVerification\Storage\Redis;
use M6Web\Component\RedisMock\RedisMockFactory;
use PHPUnit\Framework\TestCase;


abstract class BaseTest extends TestCase
{

    protected \AlexGeno\PhoneVerification\Sender\I $senderMock;
    protected Redis $storageMock;


    protected function  setUp():void{
        $this->senderMock = $this->createStub('AlexGeno\PhoneVerification\Sender\Twilio');
        $redisMock = (new RedisMockFactory())->getAdapter('\Predis\Client');
        $redisMock->flushdb();
        $this->storageMock  = new Redis($redisMock);
    }

    public function phoneNumbers(): array
    {
        return [
            'UKR' => ['+380935258272'],
            'US'  => ['5417543010'],
            'UK'  => ['+442077206312']
        ];
    }

}
