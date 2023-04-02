#Docker

````
docker-compose up --build  --force-recreate
````

````
docker-compose down
````


#Unit Tests
Run a single Method
````
vendor/bin/phpunit --filter 'AlexGeno\\PhoneVerificationTests\\Manager\\ManagerTest::testMaxAttemptsNotExceeded'  --debug
````
Check code coverage
````
vendor/bin/phpunit --coverage-text
````
#Code Sniffer
Check
````
vendor/bin/phpcs --standard=PSR12 src
````

Fix
````
vendor/bin/phpcbf --standard=PSR12 src
````
