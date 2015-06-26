<?php

require __DIR__ . '/../vendor/autoload.php';

$loop = new \React\EventLoop\StreamSelectLoop();

$dnsResolverFactory = new React\Dns\Resolver\Factory();
$dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);

$connector = new React\SocketClient\Connector($loop, $dns);
$client = new \Kalmyk\Queue\Client\QueueClient();

\React\Promise\Resolve($connector->createSocketForAddress('127.0.0.1', 8081))->then(
    function ($response) use ($client)
    {
        $socket = new \Kalmyk\Queue\Client\Socket($response, $client);
        $app = new \Kalmyk\Storage\WorkerApp($client, $response);
        $app->run();
    },
    function ($reason)
    {
        echo "Connect ERROR!\n";
    }
);

$loop->run();

