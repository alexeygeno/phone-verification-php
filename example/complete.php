<?php declare(strict_types=1);

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/storage.php');

use AlexGeno\PhoneVerification\Manager;

$options = getopt("", ['storage:', 'to:', 'otp:']);

echo "OPTIONS:\n";
print_r($options);

$storage = (new Storage())->{$options['storage']}();

try {
    (new Manager($storage))->complete($options['to'], (int)$options['otp']);
    echo "The completion has been succeeded.\n";
} catch (Exception $e) {
    echo "The completion has been failed.\n";
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
}
