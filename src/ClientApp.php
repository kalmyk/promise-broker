<?php

namespace Toa;

use \Toa\Queue\Client\QueueClient;

class ClientApp
{
    private $socket;
    private $qCli = NULL;

    private $toSend = 100000;
    private $streamLength = 1000;

    public function __construct(QueueClient $qCli, $socket)
    {
        $this->socket = $socket;
        $this->qCli = $qCli;
    }

    public function run()
    {
        $this->qCli->getEcho('Started')->then(
            function ($r) {echo "response $r\n";}
        );

        for ($i=0; $i<$this->streamLength; $i++)
        {
            $this->toSend--;
            $this->qCli->getEcho('test')->then(array($this, 'checkSent'));
        }
    }

    public function checkSent($response)
    {
//echo $this->qCli->getPendingCmdCount();
//echo " $response\n";
        if ($this->toSend>0)
        {
            $this->toSend--;
            $this->qCli->getEcho("to send {$this->toSend}")->then(array($this, 'checkSent'));
        }
        if ($this->qCli->getPendingCmdCount() == 1)  // still in the command, it is removed after the callback
            $this->socket->close();
    }
}
