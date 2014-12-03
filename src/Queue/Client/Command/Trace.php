<?php

namespace Toa\Queue\Client\Command;

use \Toa\Queue\Client;

class Trace extends \Toa\Queue\Client\QueueBaseCommand
{
    public function __construct($queueId, $quorum, $chanel = '')
    {
        parent::__construct(
            array(
                self::PKG_CMD => self::CMD_TRACE,
                self::PKG_QUEUE => $queueId,
                self::PKG_QUORUM => $quorum,
                self::PKG_CHANEL => $chanel
            )
        );
    }
}
