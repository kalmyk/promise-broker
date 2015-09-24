<?php

namespace Kalmyk\Queue\Client;

use \React\Promise\Deferred;
use \Kalmyk\Queue\QueueCommandBase;

class QueueClient extends \Kalmyk\Queue\QueueClientBase
{
    function getEcho($data)
    {
        return $this->send(
            new Command\GetEcho(),
            $data
        );
    }

    function call($queueId, $attr, $chanel = '')
    {
        return $this->send(
            new Command\Call($queueId,$chanel),
            $attr
        );
    }

    function push($queueId, $attr, $chanel = '')
    {
        return $this->send(
            new Command\Push($queueId, $chanel),
            $attr
        );
    }

    function subscribe($queueId, $chanel = '')
    {
        return $this->send(
            new Command\Subscribe($queueId,$chanel),
            NULL
        );
    }

    function unSub($queueId, $chanel = '')
    {
        return $this->send(
            new QueueCommandBase(
                array(
                    self::PKG_CMD => self::CMD_UNSUB,
                    self::PKG_CHANEL => $chanel,
                    self::PKG_QUEUE => $queueId
                )
            ),
            NULL
        );
    }

    // reset message id generator
    function marker($queueId, $prefix=NULL, $newPrefix=NULL, $newSegment=NULL)
    {
        return $this->send(
            new Command\Marker($queueId, $prefix, $newPrefix, $newSegment),
            NULL
        );
    }

    // trace all messages in the queue
    function trace($queueId, $quorum, $chanel = '')
    {
        return $this->send(
            new Command\Trace($queueId, $quorum, $chanel),
            NULL
        );
    }

    function unTrace($queueId, $chanel = '')
    {
        return $this->send(
            new QueueCommandBase(
                array(
                    self::PKG_CMD => self::CMD_UNTRACE,
                    self::PKG_CHANEL => $chanel,
                    self::PKG_QUEUE => $queueId
                )
            ),
            NULL
        );
    }
}
