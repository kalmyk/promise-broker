<?php

class QueueServerTest extends \PHPUnit_Framework_TestCase
{
    protected $client = NULL;

    public function setUp()
    {
        $loop = new \React\EventLoop\StreamSelectLoop();

        $dnsResolverFactory = new React\Dns\Resolver\Factory();
        $dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);

        $connector = new React\SocketClient\Connector($loop, $dns);
        $this->client = new \Kalmyk\Queue\Client\QueueClient();

        \React\Promise\Resolve($connector->createSocketForAddress(QUEUE_SERVER_HOST, QUEUE_SERVER_PORT))->then(
            function ($response)
            {
                $socket = new \Kalmyk\Queue\Client\Socket($response, $this->client);
                $app->run();
            },
            function ($reason)
            {
                echo "Connect ERROR!\n";
            }
        );

//        $loop->run();
    }

    public function tearDown()
    {
    }

    function testEcho()
    {
        $this->assertTrue(true);
    }
}
