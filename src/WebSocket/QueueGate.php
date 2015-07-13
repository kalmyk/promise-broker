<?php

namespace Kalmyk\WebSocket;

use \React\Promise\Deferred;

class QueueGate implements \Kalmyk\Queue\QueueConst, \Kalmyk\Queue\Client\QueueClientInterface
{
    private $onMessage = NULL;
    private $onReceive = NULL;

    public function setOnMessage(callable $onMessage)
    {
        if (isset($this->onMessage))
            throw new \Exception('unable to redeclare message callback');

        $this->onMessage = $onMessage;
    }

    public function setOnReceive(callable $onMessage)
    {
        if (isset($this->onReceive))
            throw new \Exception('unable to redeclare receive callback');

        $this->onReceive = $onMessage;
    }

    public function command($theme, $command, $data)
    {
        $command[self::PKG_CLIENT_THEME] = $theme;
        call_user_func(
            $this->onMessage,
            array(
                json_encode($command),
                $data
            ),
            ''  /* stream data */
        );
        echo "gate:"; print_r($command);
    }

    public function receive($data)
    {
echo "RECEIVE:";
print_r($data);
        $cmd = json_decode($data[0], true);
        if (isset($cmd[self::PKG_ID]) && isset($this->cmdList[$cmd[self::PKG_ID]]))
        {
            $wait = $this->cmdList[$cmd[self::PKG_ID]];
            if ($this->settle($wait, $cmd, json_decode($data[1], true)))
            {
                unset($this->cmdList[$cmd[self::PKG_ID]]);
            }
        }
        else
        {
//print_r($data);
            // unknown command ID arrived, nothing to do, could write PHP error?
        }
    }

    public function close()
    {
    }
}
