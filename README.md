##Extensible and configurable phone verification
###Requirements
- PHP >=7.4, <=8.1 
- [ Composer](https://getcomposer.org/)
- One of the supported storage clients: [ Predis](https://github.com/predis/predis), [ MongoDb](https://github.com/mongodb/mongo-php-library)
- One of the supported sender SDKs: [ Twilio](https://github.com/twilio/twilio-php), [ MessageBird](https://github.com/messagebird/php-rest-api), [Vonage ](https://github.com/Vonage/vonage-php-sdk-core)
- A smile on your face :smile:
###Installation
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
###Basic Usage

####Instantiation
```php
use AlexGeno\PhoneVerification\Storage\Redis;
use AlexGeno\PhoneVerification\Sender\Twilio;
use AlexGeno\PhoneVerification\Manager;

$storage = new Redis(new \Predis\Client('tcp://127.0.0.1:6379'));
$sender = new Twilio(new \Twilio\Rest\Client('ACXXXXXX', 'YYYYYY'));
$manager = new Manager($storage);
```
####There are two stages of the verification process

1) **The initiation** - for this stage we need both a **storage** and a **sender**. As a result a user receives an [otp](https://en.wikipedia.org/wiki/One-time_password) on the phone
   
```php
$manager->sender($sender)->initiate('+380935258272');
```
2) **The completion** - for this stage we need only a storage. The user enters the [ otp](https://en.wikipedia.org/wiki/One-time_password) to verify the phone
```php
$manager->complete('+380935258272', 1234);
```
That's basically it. More advanced usage including **otp length customization**, **rate limiters**, **messages customization** you can derive from the following sections
###Extending
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
###Advanced usage
####The Initiation stage - Rate limit params and Otp params
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
                          sprintf('You can send only %d sms per %d hours.', $count, $periodSecs/60/60)
      ]
  ],
  'otp' => [
      'length' => 4, //1000..9999
      'message' =>  (fn($otp) => sprintf('Your code is %d', $otp)), //The text a user receives
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

####The Completion stage - Rate limit params and Otp params
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
                         sprintf( 'You are trying to use an incorrect code more than'. 
                            ' %d times per %d minutes', $count, $periodSecs/60)
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
<br />**Note**: There is a default value for every single **$config** option. You should redefine only what you need

####MongoDb indexes
If you use MongoDb as a **storage** you may have noticed that the expiration functionality is based on indexes.
They can be created automatically. It's recommended though to use this option only on DEV environment. It's disabled by default.

```php
use AlexGeno\PhoneVerification\Storage\MongoDb;

$storage = new MongoDb(new \MongoDB\Client('mongodb://127.0.0.1:27017'), ['indexes'=> true]);
```
###Contribution

###Licence



### TODO

add demo and test with real senders and storages

codesniffer settings strict type on first line see https://github.com/mongodb/mongo-php-library/blob/master/phpcs.xml.dist

phpDoc

add CONTRIBUTION.md