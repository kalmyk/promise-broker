<?php

namespace Kalmyk\Queue\Server;

use React\Socket\ServerInterface;
use React\Socket\ConnectionInterface;
use Kalmyk\Queue\Server\Broker;
use Kalmyk\Queue\StreamParser;

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

        $client->setOnSendMessage(
            function($message, $data, $doEncodeData) use ($parser, $connection)
            {
                $connection->write(
                    $parser->serialize($message, $data, $doEncodeData)
                );
            }
        );

        $connection->on('data',
            function ($data) use ($connection, $parser, $client)
            {
                try {
                    $messages = $parser->parse($data, false);

                    foreach ($messages as $message)
                    {
                        $header = $message[0];
                        $data = isset($message[1])?$message[1]:NULL;
                        $this->broker->process($header, $data, $client);
                    }
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

