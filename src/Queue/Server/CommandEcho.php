<?php

namespace Kalmyk\Queue\Server;

class CommandEcho extends CommandDeferred
{
    public function process($broker, $rawData)
    {
        $broker->dSettle($this, self::RESP_OK, $rawData);
    }
}

