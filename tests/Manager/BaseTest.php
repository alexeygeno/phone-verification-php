<?php declare(strict_types=1);
namespace AlexGeno\PhoneVerificationTests\Manager;

use AlexGeno\PhoneVerification\Provider\Stub;
use AlexGeno\PhoneVerification\Storage\Redis;
use M6Web\Component\RedisMock\RedisMockFactory;
use PHPUnit\Framework\TestCase;


abstract class BaseTest extends TestCase
{

    protected Stub $providerMock;
    protected Redis $storageMock;


    protected function  setUp():void{
        $this->providerMock =  new Stub();
        $redisMock = (new RedisMockFactory())->getAdapter('\Predis\Client');
        $redisMock->flushdb();
        $this->storageMock  = new Redis($redisMock);
    }

    public function phoneNumbers(): array
    {
        return [
            'UKR' => ['+380935258272'],
            'US'  => ['+15417543010'],
            'UK'  => ['+442077206312']
        ];
    }

}
