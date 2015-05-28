<?php

namespace Kalmyk\Queue\Client\Command;

use \Kalmyk\Queue\Client;

class Trace extends \Kalmyk\Queue\Client\QueueBaseCommand
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
