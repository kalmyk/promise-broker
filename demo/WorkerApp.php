<?php

namespace QueueDemo;

use \Kalmyk\Queue\Client\QueueClient;

class WorkerApp
{
    private $socket;
    private $qCli = NULL;

    public function __construct(QueueClient $qCli, $socket)
    {
        $this->socket = $socket;
        $this->qCli = $qCli;
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

}
