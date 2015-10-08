<?php

namespace Kalmyk\WebSocket;

use Kalmyk\Queue\Server\PromiseBroker;
use Kalmyk\Queue\Server\ClientState;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WebBroker implements MessageComponentInterface {
    protected $clients;
    private $broker = NULL;

    public function __construct(PromiseBroker $broker)
    {
        $this->broker = $broker;
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);

        $client = new ClientState($this->broker);

        $this->clients[$conn] = $client;

        $client->setOnSendMessage(
            function($message, $data, $doEncodeData) use ($parser, $conn)
            {
echo "response ";
var_dump($message);
                $conn->send(
                    json_encode($message)
                );
            }
        );

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onClose(ConnectionInterface $conn)
    {
        $client = $this->clients[$conn];

        $this->clients->detach($conn);
        $this->broker->detachClient($client);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $client = $this->clients[$from];

        try {
echo "request ";
var_dump($msg);
            $this->broker->process(json_decode($msg,true), NULL, $client);
        }
        catch (\Exception $e) {
            $from->close();
        }
    }
}

