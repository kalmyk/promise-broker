<?php

namespace Kalmyk\Queue\Server;

class DeferPop extends DeferBase
{
    public function process($broker, $rawData)
    {
        $this->client->addPopState();
        $broker->dResolve($this, NULL);
        $broker->checkWaitTask($this->client);
        
        $broker->confirmRepl($this);
    }
}
