<?php

namespace Kalmyk\Queue\Client\Command;

class GetEcho extends \Kalmyk\Queue\QueueCommandBase
{
    public function __construct()
    {
        parent::__construct(
            array(
                self::PKG_CMD => self::CMD_ECHO
            )
        );
    }
}
