#Docker
####Start up
```shell
echo 'PHP_VERSION=7' > .ENV 
```
```shell
docker-compose up --build --force-recreate
```
####Tear down

```shell
docker-compose down
```
####Switching between php 7/8 versions

1. Cleanup 
```shell
rm -f composer.lock && rm -fr vendor/ 
```
2. Create .EVV file (if necessary)  and put the php version there
```text
PHP_VERSION=8
```

#Demo
ENV loading
```shell
export $(grep -v '^#' .env | xargs)
```
initiation
```shell
php example/initiate.php --sender messageBird --storage redis --to +380935258272
```
completion
```shell
php example/complete.php --storage redis --to +380935258272 --otp 1111
```

#Unit Tests
####Run a single Method
```shell
$ vendor/bin/phpunit --filter 'AlexGeno\\PhoneVerificationTests\\Manager\\ManagerTest::testMaxAttemptsNotExceeded'  --debug
```
####Check code coverage
```shell
$ vendor/bin/phpunit --coverage-text
```
#Code Sniffer
####Check
```shell
$ vendor/bin/phpcs --standard=PSR12 src
```

####Fix
```shell
$ vendor/bin/phpcbf --standard=PSR12 src
```
