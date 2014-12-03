<?php

namespace Toa\Queue\Server;

class CommandUnTrace extends CommandDeferred
{
    public function process($broker, $rawData)
    {
        if (
            !$this->checkHeader($broker, $queueId, self::PKG_QUEUE)
        )
            return false;

        $subD = $broker->getTrace($queueId,$this->chanel,$this->client->getId());
        if (!$subD)
        {
            $broker->dReject($this,
                self::ERROR_NO_QUEUE_FOUND,
                "Queue not found '$queueId'"
            );
            return NULL;
        }

        $broker->removeTrace($this->client, $subD);

        $broker->dResolve($subD, NULL);     // response to TRACE command
        $broker->dResolve($this, NULL);     // response to UNTRACE command
    }
}
