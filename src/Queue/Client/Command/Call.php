<?php

namespace Toa\Queue\Client\Command;

use \Toa\Queue\Client;

class Call extends \Toa\Queue\Client\QueueBaseCommand
{
    public function __construct($queueId, $chanel = '')
    {
        parent::__construct(
            array(
                self::PKG_CMD => self::CMD_CALL,
                self::PKG_QUEUE => $queueId,
                self::PKG_CHANEL => $chanel
            )
        );
    }
}
