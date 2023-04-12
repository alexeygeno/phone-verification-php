# phone-verification-php
A php package to verify a user via phone

In the process of development


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



add createIndexes option for mongo

try catch for transaction in redis, check discard

add demo and test with real senders and storages

rename attempts to opt_check_count

codesniffer settings strict type on first line see https://github.com/mongodb/mongo-php-library/blob/master/phpcs.xml.dist

contribute to mongoMock package