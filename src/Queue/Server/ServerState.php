<?php

namespace Kalmyk\Queue\Server;

class ServerState extends ClientState
{
    public function __construct(PromiseBroker $broker)
    {
        parent::__construct($broker);
    }
}

