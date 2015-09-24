<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\Server as ReactServer;

    require dirname(__DIR__) . '/vendor/autoload.php';

    $loop   = LoopFactory::create();
    $webSocket = new ReactServer($loop);
    $webSocket->listen(8082, '0.0.0.0');

    $dnsResolverFactory = new React\Dns\Resolver\Factory();
    $dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);

    $connector = new React\SocketClient\Connector($loop, $dns);
    $gate = new \Kalmyk\WebSocket\QueueGate();

    \React\Promise\Resolve($connector->createSocketForAddress('127.0.0.1', 8081))->then(
        function ($response) use ($gate, $webSocket, $loop)
        {
            $socket = new \Kalmyk\Queue\Client\Socket($response, $gate);
            $app = new \Kalmyk\WebSocket\WorkerApp($gate, $response);

            $server = new IoServer(
                new HttpServer(
                    new WsServer(
                        $app
                    )
                ),
                $webSocket,
                $loop
            );

        },
        function ($reason)
        {
            echo "Connect ERROR!\n";
        }
    );


//    $app = new Chat();
//    $app = new EchoMessage();

    $loop->run();
