<?php

namespace Toa\Queue\Server;

class CommandUnPop extends CommandDeferred
{
    public function process($broker, $rawData)
    {
        $this->client->delPopState();
        $broker->dResolve($this, NULL);
    }
}
