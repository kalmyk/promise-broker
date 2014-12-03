<?php

namespace Toa\Queue\Client\Command;

use \Toa\Queue\Client;

class Marker extends \Toa\Queue\Client\QueueBaseCommand
{
    public function __construct($queueId, $segment=NULL, $newSegment=NULL, $generator=NULL)
    {
        $request =array(
            self::PKG_CMD => self::CMD_MARKER,
            self::PKG_QUEUE => $queueId
        );
        if (is_string($segment))    $request[self::PKG_SEGMENT] = $segment;
        if (is_string($newSegment)) $request[self::PKG_NEW_SEGMENT] = $newSegment;
        if (is_int($generator))     $request[self::PKG_GEN_ID] = $generator;

        parent::__construct($request);
    }
}
