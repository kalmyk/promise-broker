<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\Server as ReactServer;

    require dirname(__DIR__) . '/vendor/autoload.php';

    // $loop = new \React\EventLoop\StreamSelectLoop();
    $loop   = LoopFactory::create();

    $webSocket = new ReactServer($loop);
    $webSocket->listen(8082, '0.0.0.0');

    $netSocket = new ReactServer($loop);
    $netSocket->listen(8081, '0.0.0.0');

    $broker = new Kalmyk\Queue\Server\PromiseBroker();

    $openQueueLogic = new Kalmyk\Queue\Server\Socket(
        $netSocket,
        $broker
    );

//    $app = new Chat();
//    $app = new EchoMessage();

    $app = new \Kalmyk\WebSocket\WebBroker($broker);

    $server = new IoServer(
        new HttpServer(
            new WsServer(
                $app
            )
        ),
        $webSocket,
        $loop
    );

    $loop->run();

