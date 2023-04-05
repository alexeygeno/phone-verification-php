<?php

namespace AlexGeno\PhoneVerification\Sender;

use Twilio\Rest\Client;

class Twilio implements I{

	protected Client $client;
	protected array $options;

	public function __construct(Client $client, $options){
		$this->client = $client;
		$this->options = $options;
	}

	public function invoke($to, $text):bool{
		$this->client->messages->create(
            $to,
			array_merge($this->options, ['body' => $text])
		);
	}

}