<?php

namespace AlexGeno\PhoneVerification\Sender;

use MessageBird\Client;

/**
 * Class MessageBird
 * @see https://developers.messagebird.com/api/sms-messaging/#send-outbound-sms
 * @package AlexGeno\PhoneVerification\Sender
 */
class MessageBird implements I{

	protected Client $client;
	protected \MessageBird\Objects\Message $message;

	public function __construct(Client $client, \MessageBird\Objects\Message $message ){
		$this->client = $client;
		$this->message = $message;
	}


	public function invoke(string $to, string $text){
        $this->message->recipients = [$to];
        $this->message->body = $text;
		return $this->client->messages->create($this->message);
	}
}