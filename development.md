#Docker

````
docker-compose up --build  --force-recreate
````

````
docker-compose down
````


#Tests
Run a single Method
````
vendor/bin/phpunit --filter 'AlexGeno\\PhoneVerificationTests\\Manager\\ManagerTest::testMaxAttemptsNotExceeded'  --debug
````
Check code coverage
````
vendor/bin/phpunit --coverage-text
````