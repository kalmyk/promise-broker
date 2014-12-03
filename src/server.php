<?php

date_default_timezone_set('UTC');

require __DIR__ . '/../vendor/autoload.php';

//$loop = React\EventLoop\Factory::create();
// EventBase detected problem with unsent stream buffer
$loop = new \React\EventLoop\StreamSelectLoop();

$socket = new React\Socket\Server($loop);
$socket->listen(isset($argv[1])?$argv[1]:8081, '0.0.0.0');

$openQueueLogic = new Toa\Queue\Server\Socket(
    $socket,
    new Toa\Queue\Server\PromiseBroker()
);

echo "Started.\n";
$loop->run();
