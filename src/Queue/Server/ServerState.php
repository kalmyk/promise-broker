<?php

namespace Kalmyk\Queue\Server;

class ServerState extends ClientState
{
    public function __construct(PromiseBroker $broker)
    {
        parent::__construct($broker);
    }

    public function connect()
    {
        $this->send(
            array(
                json_encode(array(self::PKG_CMD => self::CMD_PEAR)),
                json_encode(NULL)
            )
        );
    }
}

