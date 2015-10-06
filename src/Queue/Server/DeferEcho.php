<?php

namespace Kalmyk\Queue\Server;

class DeferEcho extends DeferBase
{
    public function process($broker, $rawData)
    {
        $broker->dSettle($this, self::RESP_OK, $rawData, false);
    }
}

