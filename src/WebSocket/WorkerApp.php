<?php

namespace Kalmyk\WebSocket;

use Kalmyk\Queue\Client\QueueClient;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class WorkerApp implements MessageComponentInterface {
    protected $clients;

    private $socket;
    private $qCli = NULL;

    public function __construct(QueueClient $qCli, $socket)
    {
        $this->socket = $socket;
        $this->qCli = $qCli;

        $this->clients = new \SplObjectStorage;
    }

    public function run()
    {
        $this->qCli->subscribe('job')->then(NULL,NULL,
            function ($task)
            {
                echo "task {$task->getData()}\n";
                $task->resolve(true);
            }
        );
        $this->qCli->pull();
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

echo ">>>\n";
$m = explode("\n", $msg);
$cmd = json_decode($m[0], true);
var_dump($cmd);

        $this->qCli->trace($cmd['q'], 0)->then(
            NULL,NULL,
            function ($task) use ($from)
            {
                var_dump($task->getData());
                $from->send($task->getData());
                $task->resolve(true);
            }
        );

        // send echo message only to the client sent it
        foreach ($this->clients as $client) {
            if ($from === $client) {
                $client->send('{"c":"answer","#":1}'."\r\n".'{"data":"text"}');
                break;
            }
        }

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send($msg);
            }
        }
    }

    public function notify($msg)
    {
        echo "notify $msg for ".count($this->clients)." clients\n";

        foreach ($this->clients as $client)
        {
            $client->send($msg);
        }
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
}

