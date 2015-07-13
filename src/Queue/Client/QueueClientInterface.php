<?php

namespace Kalmyk\Queue\Client;

interface QueueClientInterface
{
    // $onMessage ($message, $data)
    public function setOnMessage(callable $onMessage);
    public function receive($data);
    public function close();
}
