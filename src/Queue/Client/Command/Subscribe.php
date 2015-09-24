<?php

namespace Kalmyk\Queue\Client\Command;

use \Kalmyk\Queue\Client;

class Subscribe extends \Kalmyk\Queue\QueueCommandBase
{
    public function __construct($queueId, $chanel = '')
    {
        parent::__construct(
            array(
                self::PKG_CMD => self::CMD_SUB,
                self::PKG_QUEUE => $queueId,
                self::PKG_CHANEL => $chanel
            )
        );
    }
}
