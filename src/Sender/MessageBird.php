<?php

namespace AlexGeno\PhoneVerification\Sender;

use MessageBird\Client;

class MessageBird implements I{

	protected Client $client;
	protected \MessageBird\Objects\Message $message;

	public function __construct(Client $client, \MessageBird\Objects\Message $message ){
		$this->client = $client;
		$this->message = $message;
	}

	public function invoke($to, $text):bool{
        $this->message->recipients = [$to];
        $this->message->body = [$to];
		$this->client->messages->create($this->message);
	}

}