<?php

namespace Kalmyk\Queue\Server;

class DeferSub extends DeferBaseSub
{
    public function process($broker, $rawData)
    {
        if (
            !$this->checkHeader($broker, $taskId, self::PKG_ID) ||
            !$this->checkHeader($broker, $queueId, self::PKG_QUEUE)
        )
            return false;

        if ($broker->getTrace($queueId, $this->chanel, $this->client->getId()) ||
            $broker->getSub($queueId, $this->chanel, $this->client->getId())
        )
        {
            $broker->dReject($this,
                self::ERROR_ALREADY_SUBSCRIBED,
                "Queue already subscribed '$queueId'"
            );
            return NULL;
        }
        $broker->addSub($this);
//        $broker->sendToPear($this);
    }

    public function sendToClient($mode, $header, $rawData, $doEncodeData)
    {
        // sending task to worker, mark worker busy
        if ($mode === self::RESP_EMIT)
            $this->client->delPopState();

        parent::sendToClient($mode, $header, $rawData, $doEncodeData);
    }
}
