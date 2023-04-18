<?php declare(strict_types=1);

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/sender.php');
require(__DIR__ . '/storage.php');

use AlexGeno\PhoneVerification\Manager;

$options = getopt("", ['sender:', 'storage:', 'to:']);

echo "OPTIONS:\n";
print_r($options);

$storage = (new Storage())->{$options['storage']}();
$sender = (new Sender())->{$options['sender']}();

try {
    $response = (new Manager($storage))->sender($sender)->initiate($options['to']);
    echo "API RESPONSE:\n";
    print_r($response);
    echo "The initiation has been succeeded. Check your phone for otp!\n";
} catch (Exception $e) {
    echo "The initiation has been failed.\n";
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
}
