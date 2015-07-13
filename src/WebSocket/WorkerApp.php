<?php

namespace Kalmyk\WebSocket;

use Kalmyk\WebSocket\QueueGate;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WorkerApp implements MessageComponentInterface {
    protected $clients;

    private $socket;
    private $qCli = NULL;

    public function __construct(QueueGate $qCli, $socket)
    {
        $this->socket = $socket;
        $this->qCli = $qCli;
        $this->qCli->setOnReceive(array($this, 'onQueueResponce'));

        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

echo ">>>\n";
$m = explode("\n", $msg);
$cmd = json_decode($m[0], true);
var_dump($cmd);

        $this->qCli->command($from->resourceId, $cmd, NULL);
    }

    public function onQueueResponce($data)
    {
        echo "RESPONSE:";
        var_dump($data);
    }
}

