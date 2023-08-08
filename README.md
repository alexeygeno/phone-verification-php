# Phone Verification

[![Build Status](https://github.com/alexeygeno/phone-verification-php/workflows/PHPUnit/badge.svg)](https://github.com/alexeygeno/phone-verification-php/actions)
[![Build Status](https://github.com/alexeygeno/phone-verification-php/workflows/CodeSniffer/badge.svg)](https://github.com/alexeygeno/phone-verification-php/actions)
[![Coverage Status](https://coveralls.io/repos/github/alexeygeno/phone-verification-php/badge.svg)](https://coveralls.io/github/alexeygeno/phone-verification-php)

Signing in or signing up on a modern website or mobile app typically follows these steps:
- A user initiates verification by submitting a phone number
- The user receives an SMS or a call with a one-time password [(OTP)](https://en.wikipedia.org/wiki/One-time_password)
- The user completes verification by submitting the [OTP](https://en.wikipedia.org/wiki/One-time_password)

This extensible and configurable library allows to set this up just with a few lines of code
## Requirements
- Supported PHP versions: 7.4, 8.0, 8.1, 8.2
- [ Composer](https://getcomposer.org/)
- Supported storage clients: [ Predis](https://github.com/predis/predis), [ MongoDb](https://github.com/mongodb/mongo-php-library)
- Supported sender SDKs: [ Twilio](https://github.com/twilio/twilio-php), [ MessageBird](https://github.com/messagebird/php-rest-api), [Vonage ](https://github.com/Vonage/vonage-php-sdk-core)
- A smile on your face :smile:

## Installation
```shell
composer require alexgeno/phone-verification
```
**Note:** All supported storage clients and sender SDKs are in **require-dev** section. In a production environment you must manually install only what you use.
## Basic Usage

### Instantiation
[ Predis](https://github.com/predis/predis) as a **storage** and  [ Twilio](https://github.com/twilio/twilio-php) as a **sender** are used for the demonstration
```php
use AlexGeno\PhoneVerification\Storage\Redis;
use AlexGeno\PhoneVerification\Sender\Twilio;
use AlexGeno\PhoneVerification\Manager;

$storage = new Redis(new \Predis\Client('tcp://127.0.0.1:6379'));
$sender = new Twilio(new \Twilio\Rest\Client('ACXXXXXX', 'YYYYYY'), ['from' => '+442077206312']);
$manager = new Manager($storage);
```
### There are two stages in the verification process

**Initiation** -  a **storage** and a **sender** are required for this stage. A user submits a phone and as a result receives an [otp](https://en.wikipedia.org/wiki/One-time_password)

```php
$manager->sender($sender)->initiate('+15417543010');
```
**Completion** - only a storage is required for this stage. The user submits the [ otp](https://en.wikipedia.org/wiki/One-time_password) to verify the phone
```php
$manager->complete('+15417543010', 1234);
```
That's basically it. More advanced usage including **otp length customization**, **rate limiters**, **messages customization** you can derive from the following sections.

## Demo
**Initiation** 
```shell
php example/initiate.php --storage redis --sender messageBird --to +15417543010
```
**Completion** 
```shell
php example/complete.php --storage redis --to +15417543010 --otp 1111
```
**Note**: See [DEVELOPMENT.md](DEVELOPMENT.md) as an option for how to set up a development environment

## Extending
To add a new **sender** just create a new class
```php
namespace AlexGeno\PhoneVerification\Sender;

class Plivo implements I
{ 
    //...
}
```
To add a new **storage** just create a new class
```php
namespace AlexGeno\PhoneVerification\Storage;

class DynamoDb implements I
{ 
    //...
}
```
## Advanced usage
Rate limit params and otp params might be customized

**Initiation**
```php
use AlexGeno\PhoneVerification\Storage\Redis;
use AlexGeno\PhoneVerification\Sender\Twilio;
use AlexGeno\PhoneVerification\Manager;
use AlexGeno\PhoneVerification\Exception\RateLimit;

$config = [
    'rate_limits' => [
        'initiate' => [
            'period_secs' => 86400,
            'count' => 10,
            'message' =>
                fn($phone, $periodSecs, $count) =>
                    sprintf('You can send only %d sms in %d hours.', $count, $periodSecs / 60 / 60)
        ]
    ],
    'otp' => [
        'length' => 4, // 1000..9999
        'message' =>  fn($otp) => sprintf('Your code is %d', $otp) // The text a user receives
    ]
];

$storage = new Redis(new \Predis\Client('tcp://127.0.0.1:6379'));
$sender = new Twilio(new \Twilio\Rest\Client('ACXXXXXX', 'YYYYYY'), ['from' => '+442077206312']);

try {
    (new Manager($storage, $config))->sender($sender)->initiate('+15417543010');
} catch (RateLimit $e) {
    echo $e->getMessage(); // 'You can send only 10 sms in 24 hours'
}
```

**Completion**
```php
use AlexGeno\PhoneVerification\Storage\Redis;
use AlexGeno\PhoneVerification\Manager;
use AlexGeno\PhoneVerification\Exception\RateLimit;
use AlexGeno\PhoneVerification\Exception\Otp;

$config = [
    'rate_limits' => [
        'complete' => [
            'period_secs' => 300,
            'count' => 5,
            'message' =>
                fn($phone, $periodSecs, $count) =>
                    sprintf('You are trying to use an incorrect code %d times in %d minutes', $count, $periodSecs / 60)
        ]
    ],
    'otp' => [
        'message_expired' =>
            fn($periodSecs, $otp) =>
                sprintf('Code is expired. You have only %d minutes to use it.', $periodSecs / 60),
        'message_incorrect' =>  fn($otp) => 'Code is incorrect'
    ]
];
$storage = new Redis(new \Predis\Client('tcp://127.0.0.1:6379'));

try {
    (new Manager($storage, $config))->complete('+15417543010', 1234);
} catch (RateLimit | Otp $e) {
    // 'Code is incorrect' ||
    // 'Code is expired. You have only 5 minutes to use it.' ||
    // 'You are trying to use an incorrect code 5 times in 5 minutes'
    echo $e->getMessage();
}
```
**Note:** Of course,  you can define all **$config** options and instantiate all classes at the same place in your code.
It is split here just to make it more clear what belongs to **the initiation stage** and what to **the completion stage**
<br />**Note:** Each **$config** option has a default value. You should redefine only what you need.

### MongoDb indexes
If you use MongoDb as a **storage** you may have noticed that the expiration functionality is based on indexes.
They can be created automatically. It's recommended though to use this option only in a non-production environment. It's disabled by default.

```php
use AlexGeno\PhoneVerification\Storage\MongoDb;

$storage = new MongoDb(new \MongoDB\Client('mongodb://127.0.0.1:27017'), ['indexes'=> true]);
```
## Contributing
See [CONTRIBUTING.md](CONTRIBUTING.md)

## Development
See [DEVELOPMENT.md](DEVELOPMENT.md) as an option for how to set up a development environment 

## Licence
The code for **Phone Verification** is distributed under the terms of the [MIT](LICENSE.txt) license.
