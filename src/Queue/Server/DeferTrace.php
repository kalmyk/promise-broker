<?php

namespace Kalmyk\Queue\Server;

class DeferTrace extends DeferBaseSub
{
    public $quorum = 1;

    public function process($broker, $rawData)
    {
        if (
            !$this->checkHeader($broker, $queueId, self::PKG_QUEUE) ||
            !$this->checkHeader($broker, $this->quorum, self::PKG_QUORUM)
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

        $broker->addTrace($this);
    }

    public function sendToClient($mode, $header, $rawData, $doEncodeData)
    {
        // sending task to worker, mark worker busy
        if ($mode === self::RESP_EMIT)
            $this->client->delPopState();

        parent::sendToClient($mode, $header, $rawData, $doEncodeData);
    }
}

