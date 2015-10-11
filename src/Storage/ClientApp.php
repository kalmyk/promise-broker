<?php

namespace Kalmyk\Storage;

use \Kalmyk\Queue\Client\QueueClient;

class ClientApp
{
    private $qCli = NULL;

    private $toSend = 100000;
    private $streamLength = 1000;

    public function __construct(QueueClient $qCli)
    {
        $this->qCli = $qCli;
    }

    public function run_test_echo()
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

    public function run_test_sub($count)
    {
        $this->qCli->push('twitter', "data $count");


        $this->qCli->call('job', "data $count")->then(
            function ($response) use ($count)
            {
//                echo "response $response\n";
                if ($count > 0)
                    $this->run_test_sub($count-1);
                else
                    $this->socket->close();
            }
        );
    }

    public function run()
    {
//        for ($i=0; $i<2; $i++)

            $this->run_test_sub(10);

//        $this->run_test_echo();
    }
}
