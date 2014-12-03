<?php

namespace Toa\Queue\Client\Command;

class GetEcho extends \Toa\Queue\Client\QueueBaseCommand
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
