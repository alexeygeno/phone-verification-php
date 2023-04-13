## Widely configurable and extendable phone verification
 - ###Requirements
 - ###Installation
 - ###Usage
 - ###Configuration
 - ###Extending
 - ###Demo
 - ###Contribution




Reqirements

```php
try{
    $manager->initiate('+380935258272');
}catch(\Exception\RateLimit $e){
    $e->getMessage();
}
```


```php
try{
    $manager->complete('+380935258272', 2345);
}catch(\Exception\RateLimit $e){
    $e->getMessage();
}catch(\Exception\Otp $e){
    $e->getMessage();
}
```

### TODO




try catch for transaction in redis, check discard

add demo and test with real senders and storages

codesniffer settings strict type on first line see https://github.com/mongodb/mongo-php-library/blob/master/phpcs.xml.dist

phpDoc

add Manager::sender()