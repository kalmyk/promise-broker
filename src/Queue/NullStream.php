<?php

namespace Kalmyk\Queue;

use \Kalmyk\Queue\StreamParser;

class NullStream
{
    private $onReceive;
    private $stream;
    private $buffer = '';
    private $parser = NULL;
    public $parseData = NULL;

    public function __construct($parseData)
    {
        $this->parser = new StreamParser();
        $this->parseData = $parseData;
    }

    function setStream($stream)
    {
        $this->stream = $stream;
    }

    function setOnReceive($onReceive)
    {
        $this->onReceive = $onReceive;
    }

    function send($header, $data, $doEncodeData)
    {
        $this->buffer .= $this->parser->serialize($header, $data, $doEncodeData);
//fwrite(STDERR, ">>>>".$this->buffer);
    }

    function parse($buffer)
    {
        $messages = $this->parser->parse($buffer, $this->parseData);

        foreach ($messages as $msg)
            call_user_func($this->onReceive, $msg);
    }

    function processBuffer()
    {
        if (strlen($this->buffer) == 0)
            return false;

        $this->stream->parse($this->buffer);
        $this->buffer = '';

        return true;
    }
}

