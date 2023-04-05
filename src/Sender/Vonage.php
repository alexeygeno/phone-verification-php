<?php

namespace AlexGeno\PhoneVerification\Sender;

use Vonage\Client;
use Vonage\SMS\Message\SMS;

class Vonage implements I{

    protected Client $client;
    protected string $brandName;

    public function __construct(Client $client, string $brandName){
        $this->client = $client;
        $this->brandName = $brandName;
    }

    public function invoke(string $to, string $text):bool{
        $this->client->sms()->send(
            new SMS($to, $this->brandName, $text, 'unicode')
        );
    }

}