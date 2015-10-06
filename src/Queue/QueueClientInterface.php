<?php

namespace Kalmyk\Queue;

interface QueueClientInterface
{
    // $onMessage ($header, $data)
    public function setOnMessage(callable $onMessage);
    public function receive($header, $data);
    public function close();
}
