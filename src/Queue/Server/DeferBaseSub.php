<?php

namespace Kalmyk\Queue\Server;

class DeferBaseSub extends DeferBase
{
    public $queue = '';
    private $pushStack = array();

    public function __construct($header, $client)
    {
        parent::__construct($header, $client);
        $this->queue = isset($header[self::PKG_QUEUE]) ? $header[self::PKG_QUEUE] : '';
    }

    public function pushStack($header, $rawData)
    {
        $this->pushStack[] = array($header, $rawData);
    }

    public function popStack()
    {
        if (count($this->pushStack) == 0)
            return false;
        else
            return array_shift($this->pushStack);
    }
}

