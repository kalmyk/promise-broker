<?php

namespace Kalmyk\Queue\Server;

class CommandPop extends CommandDeferred
{
    public function process($broker, $rawData)
    {
        $this->client->addPopState();
        $broker->dResolve($this, NULL);
        $broker->checkWaitTask($this->client);
        
        $broker->confirmRepl($this);
    }
}
