<?php

namespace Kalmyk\Queue\Server;

class DeferCall extends DeferBase
{
    public $data;

    public function process($broker, $rawData)
    {
        if (!$this->checkHeader($broker, $queueId, self::PKG_QUEUE))
            return false;

        $this->data = $rawData;

        $queue = $broker->getSubStack($queueId, $this->chanel);
        if (0 == count($queue))
        {
            $broker->dReject($this,
                self::ERROR_NO_QUEUE_FOUND,
                "Queue not found '$queueId'"
            );
            return NULL;
        }

        foreach ($queue as $workerId => $subD)
        {
            if ($subD->client->getPopState())
            {
                $broker->callWorker($queueId, $this, $subD);
                return NULL;
            }
        }
        // all workers are bisy, keep the message till to the one of them free
        $broker->waitForResolver($queueId, $this);
    }

    public function responseArrived($broker, $mode, $rawData)
    {
        if ($mode !== self::RESP_EMIT)
            $broker->settleDone($this);

        // if Client waits for the Worker response send the package to the client
        $broker->dSettle(
            $this,
            $mode,
            $rawData,
            false
        );
    }
}

