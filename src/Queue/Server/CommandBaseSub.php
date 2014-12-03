<?php

namespace Toa\Queue\Server;

class CommandBaseSub extends CommandDeferred
{
    public $queue = '';

    public function __construct($header, $client)
    {
        parent::__construct($header, $client);
        $this->queue = isset($header[self::PKG_QUEUE]) ? $header[self::PKG_QUEUE] : '';
    }
}

