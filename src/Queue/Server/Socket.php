<?php

namespace Toa\Queue\Server;

use React\Socket\ServerInterface;
use React\Socket\ConnectionInterface;
use Toa\Queue\Server\Broker;
use Toa\Queue\StreamParser;

class Socket
{
    private $broker = NULL;

    public function __construct(ServerInterface $socket, PromiseBroker $broker)
    {
        $this->broker = $broker;
        $socket->on('connection', array($this, 'handleConnection'));
    }

    public function handleConnection(ConnectionInterface $connection)
    {
        $parser = new StreamParser();
        $client = new ClientState($this->broker);

        $this->broker->attachClient($client);

        $client->onMessage(
            function($message, $data) use ($parser, $connection)
            {
                $connection->write(
                    $parser->serialize($message, $data)
                );
            }
        );

        $connection->on('data',
            function ($data) use ($connection, $parser, $client)
            {
                try {
                    $messages = $parser->parse($data);

                    foreach ($messages as $message)
                        $this->broker->process($message, $client);
                }
                catch (\Exception $e) {
                    $connection->close();
                    return;
                }
            }
        );

        $connection->on('close', function() use ($client)
        {
            $this->broker->detachClient($client);
        });
    }
}

