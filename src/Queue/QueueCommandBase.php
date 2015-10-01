<?php

namespace Kalmyk\Queue;

use \React\Promise\Deferred;

class QueueCommandBase implements QueueConst
{
    protected $command = NULL;

    public function __construct($command)
    {
        $this->command = $command;
    }

    public function then(QueueCommandBase $atResolve = NULL, QueueCommandBase $atReject = NULL, QueueCommandBase $atProgress = NULL)
    {
        if ($atResolve)
            $this->command[self::PKG_STACK][self::RESP_OK] = $atResolve->getCommandData();
        if ($atReject)
            $this->command[self::PKG_STACK][self::RESP_ERROR] = $atReject->getCommandData();
        if ($atProgress)
            $this->command[self::PKG_STACK][self::RESP_EMIT] = $atProgress->getCommandData();  // TODO: move recursion to send
    }

    public function getCommandData()
    {
        return $this->command;
    }
}

