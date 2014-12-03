<?php

namespace Toa\Queue\Client;

use \React\Promise\Deferred;

class QueueBaseCommand implements \Toa\Queue\QueueConst
{
    protected $command = NULL;
    
    public function __construct($command)
    {
        $this->command = $command;
    }
    
    public function then(QueueBaseCommand $atResolve = NULL, QueueBaseCommand $atReject = NULL, QueueBaseCommand $atProgress = NULL)
    {
        if ($atResolve)
            $this->command[self::PKG_STACK][self::RESP_OK] = $atResolve->getCommandData(NULL,NULL);
        if ($atReject)
            $this->command[self::PKG_STACK][self::RESP_ERROR] = $atReject->getCommandData(NULL,NULL);
        if ($atProgress)
            $this->command[self::PKG_STACK][self::RESP_EMIT] = $atProgress->getCommandData(NULL,NULL);  // TODO: move recursion to send
    }
    
    public function getCommandData()
    {
        return $this->command;
    }
}
