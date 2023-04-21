# Phone Verification #

[![Build Status](https://github.com/alexeygeno/phone-verification-php/workflows/PHPUnit/badge.svg)](https://github.com/alexeygeno/phone-verification-php/actions)
[![Build Status](https://github.com/alexeygeno/phone-verification-php/workflows/CodeSniffer/badge.svg)](https://github.com/alexeygeno/phone-verification-php/actions)
[![Coverage Status](https://coveralls.io/repos/github/alexeygeno/phone-verification-php/badge.svg)](https://coveralls.io/github/alexeygeno/phone-verification-php)

The usual way to sign in/sign up on a modern website or mobile app is:
- A user initiates verification submitting a phone number 
- The user receives an sms or call with an [ otp](https://en.wikipedia.org/wiki/One-time_password)
- The user completes verification submitting the [ otp](https://en.wikipedia.org/wiki/One-time_password)

This extensible and configurable library allows to set this up just with a few lines of code.
## Requirements ##
- One of the supported PHP versions: 7.4, 8.0, 8.1
- [ Composer](https://getcomposer.org/)
- One of the supported storage clients: [ Predis](https://github.com/predis/predis), [ MongoDb](https://github.com/mongodb/mongo-php-library)
- One of the supported sender SDKs: [ Twilio](https://github.com/twilio/twilio-php), [ MessageBird](https://github.com/messagebird/php-rest-api), [Vonage ](https://github.com/Vonage/vonage-php-sdk-core)
- A smile on your face :smile:

## Installation ##
This package
```shell
composer require alexgeno/phone-verification
```
Predis as an option
```shell
composer require predis/predis
```
Twilio as an option
```shell
composer require twilio/sdk
```
## Basic Usage ##

### Instantiation ###
```php
use AlexGeno\PhoneVerification\Storage\Redis;
use AlexGeno\PhoneVerification\Sender\Twilio;
use AlexGeno\PhoneVerification\Manager;

$storage = new Redis(new \Predis\Client('tcp://127.0.0.1:6379'));
$sender = new Twilio(new \Twilio\Rest\Client('ACXXXXXX', 'YYYYYY'));
$manager = new Manager($storage);
```
### There are two stages of the verification process ###

1) **The initiation** -  a **storage** and a **sender** are required for this stage. A user submits a phone and as a result receives an [otp](https://en.wikipedia.org/wiki/One-time_password)
   
```php
$manager->sender($sender)->initiate('+380935258272');
```
2) **The completion** - only a storage is required for this stage. The user submits the [ otp](https://en.wikipedia.org/wiki/One-time_password) to verify the phone
```php
$manager->complete('+380935258272', 1234);
```
That's basically it. More advanced usage including **otp length customization**, **rate limiters**, **messages customization** you can derive from the following sections.

## Demo
**The initiation**
```shell
php example/initiate.php --storage redis --sender messageBird --to +380935258272
```
**The completion**
```shell
php example/complete.php --storage redis --to +380935258272 --otp 1111
```
**Note**: Don't forget to rename *example/.example.env*   to *example/.env* and fill in it with actual data.

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
**The Initiation:** Rate limit params and Otp params
```php
use AlexGeno\PhoneVerification\Storage\Redis;
use AlexGeno\PhoneVerification\Sender\Twilio;
use AlexGeno\PhoneVerification\Manager;
use AlexGeno\PhoneVerification\Exception\RateLimit;

$config = [
  'rate_limits' => [
      'initiate' => [
          'period_secs' => 86400, 'count' => 10,
          'message' => fn($phone, $periodSecs, $count) => 
                          sprintf('You can send only %d sms in %d hours.', $count, $periodSecs/60/60)
      ]
  ],
  'otp' => [
      'length' => 4, //1000..9999
      'message' =>  fn($otp) => sprintf('Your code is %d', $otp), //The text a user receives
  ]
];
$storage = new Redis(new \Predis\Client('tcp://127.0.0.1:6379'));
$sender = new Twilio(new \Twilio\Rest\Client('ACXXXXXX', 'YYYYYY'));

try
{
   (new Manager($storage, $config))->sender($sender)->initiate('+380935258272');
}
catch(RateLimit $e)
{
    echo $e->getCode(); //RateLimit::CODE_INITIATE
    echo $e->getMessage();//You can send only 10 sms per 24 hours
}
```

**The Completion**: Rate limit params and Otp params
```php
use AlexGeno\PhoneVerification\Storage\Redis;
use AlexGeno\PhoneVerification\Manager;
use AlexGeno\PhoneVerification\Exception\RateLimit;
use AlexGeno\PhoneVerification\Exception\Otp;

$config = [
  'rate_limits' => [
      'complete' => [
         'period_secs' => 300, 'count' => 5,
         'message' => fn($phone, $periodSecs, $count) => 
                         sprintf( 'You are trying to use an incorrect code'. 
                            ' %d times in %d minutes', $count, $periodSecs/60)
     ]
  ],
  'otp' => [
      'message_expired' =>  fn($periodSecs, $otp) => 
                               sprintf( 'Code is expired '.
                                  'You have only %d minutes to use it.', $periodSecs/60),
      'message_incorrect' =>  fn($otp) => 'Code is incorrect',
  ]
];
$storage = new Redis(new \Predis\Client('tcp://127.0.0.1:6379'));

try
{    
   (new Manager($storage, $config))->complete('+380935258272', 1234);
}
//Both RateLimit and Otp extends AlexGeno\PhoneVerification\Exception
//So you can use it instead to have the only one catch block
catch(RateLimit $e)
{ 
    echo $e->getCode(); //RateLimit::CODE_COMPLETE
    echo $e->getMessage();//'You are trying to use an incorrect code more than 5 times per 5 minutes'
}
catch(Otp $e)
{ 
    echo $e->getCode();//Otp::CODE_INCORRECT || Otp::CODE_EXPIRED
    echo $e->getMessage();//'Code is incorrect' || 'Code is expired. You have only 5 minutes to use it.'
}
```
**Note:** Of course,  you can define all **$config** options and instantiate all classes at the same place in your code.
It is split here just to make it more clear what belongs to **the initiation stage** and what to **the completion stage**
<br />**Note**: Every single **$config** option has a default value. You should redefine only what you need.

### MongoDb indexes
If you use MongoDb as a **storage** you may have noticed that the expiration functionality is based on indexes.
They can be created automatically. It's recommended though to use this option only on DEV environment. It's disabled by default.

```php
use AlexGeno\PhoneVerification\Storage\MongoDb;

$storage = new MongoDb(new \MongoDB\Client('mongodb://127.0.0.1:27017'), ['indexes'=> true]);
```

## Contribution

## Licence

The code for **Phone Verification** is distributed under the terms of the [MIT](LICENSE.txt) license.

### TODO


README.md Installation for dev and prod with collapsed block for prod
Docker compose - php instead of php-fpm, no foreign ports, clean up

add CONTRIBUTION.md  docker set up, unit tests, cs, example


Check spelling
Push package at packagist


chekc that tests autoloads only for dev env

rename git username to alexgeno and change it everywhere in code
alexgeno@gmail.com