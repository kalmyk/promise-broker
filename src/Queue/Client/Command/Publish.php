<?php

namespace Kalmyk\Queue\Client\Command;

use \Kalmyk\Queue\Client;

class Publish extends \Kalmyk\Queue\Client\QueueBaseCommand
{
    public function __construct($queueId, $chanel = '')
    {
        parent::__construct(
            array(
                self::PKG_CMD => self::CMD_PUB,
                self::PKG_QUEUE => $queueId,
                self::PKG_CHANEL => $chanel
            )
        );
    }
}
