<?php

namespace Kalmyk\Queue\Server;

class CommandUnSub extends CommandDeferred
{
    public function process($broker, $rawData)
    {
        if (
            !$this->checkHeader($broker, $queueId, self::PKG_QUEUE)
        )
            return false;

        $subD = $broker->getSub($queueId,$this->chanel,$this->client->getId());
        if (!$subD)
        {
            $broker->dReject($this,
                self::ERROR_NO_QUEUE_FOUND,
                "Queue not found '$queueId'"
            );
            return NULL;
        }

        $broker->removeSub($this->client, $subD);

        $broker->dResolve($subD, NULL);   // response to subscribe command
        $broker->dResolve($this, NULL);     // response to unsubscribe command
    }
}
