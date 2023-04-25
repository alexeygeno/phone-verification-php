## Start up
Set a PHP version. It essentially means which .Dockerfile will be used
```shell
export PHP_VERSION=7
```
Create *example/.env* and fill in it with actual data
```shell
cp example/.example.env example/.env 
```
Build docker images and start containers
```shell
docker compose up -d
```
Get into the container
```shell
docker compose exec php sh
```
## PHPUnit
Run a method
```shell
vendor/bin/phpunit --filter 'AlexGeno\\PhoneVerificationTests\\Manager\\DefaultConfigTest::testExpiredOtpException'  --debug
```
Check code coverage
```shell
vendor/bin/phpunit --coverage-text
```
## CodeSniffer
Check
```shell
vendor/bin/phpcs 
```

Fix
```shell
vendor/bin/phpcbf
```

## CLI Demo

Initiation
```shell
php example/initiate.php --storage redis --sender messageBird --to +380935258272
```
Completion
```shell
php example/complete.php --storage redis --to +380935258272 --otp 1111
```