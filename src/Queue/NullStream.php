<?php

namespace Toa\Queue;

class NullStream
{
    private $onReceive;
    private $stream;
    private $buffer = array();

    function setStream($stream)
    {
        $this->stream = $stream;
    }

    function setOnReceive($onReceive)
    {
        $this->onReceive = $onReceive;
    }

    function send($data)
    {
        $this->buffer[] = $data;
    }
    
    function processBuffer()
    {
        if (count($this->buffer) == 0)
            return false;

        $data = array_shift($this->buffer);
        call_user_func($this->stream->onReceive, $data);
        return true;
    }
}

