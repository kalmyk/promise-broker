<?php

namespace Kalmyk\Queue\Server;

class DeferUnPop extends DeferBase
{
    public function process($broker, $rawData)
    {
        $this->client->delPopState();
        $broker->dResolve($this, NULL);
    }
}
