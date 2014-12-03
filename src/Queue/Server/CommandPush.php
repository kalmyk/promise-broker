<?php

namespace Toa\Queue\Server;

class CommandPush extends CommandDeferred
{
    private $queueId = '';
    private $responseQuorum = 0;
    private $pushQuorum = 0;
    private $traceCount = 0;
    private $rawData = NULL;

    private function cmdPush_GetQuorum($broker, &$quorum, &$tracers, $chanelId)
    {
        $queue = $broker->getTraceStack($this->queueId, $chanelId);
        foreach ($queue as $workerId => $subD)
        {
            $tracers[] = $subD;
            $quorum = max($quorum, $subD->quorum);
        }
    }

    private function nofifySubCnahel($broker, $chanel, $header)
    {
        $queue = $broker->getSubStack($this->queueId, $chanel);
        foreach ($queue as $clientId => $subD)
            $subD->client->checkPush($broker, $subD, $header, $this->rawData);

        return count($queue);
    }

    private function nofifySubscribers($broker)
    {
        $header = array(
            self::PKG_CHANEL => $this->chanel,
            self::PKG_CLIENT => $this->client->getId()
        );

        $sends = $this->traceCount + $this->nofifySubCnahel($broker, $this->chanel, $header);

        if ($this->chanel != '')
            $sends += $this->nofifySubCnahel($broker, '', $header);

        $broker->dResolve(
            $this, 
            array(self::RESP_QUORUM => $this->responseQuorum, self::RESP_SEND => $sends)
        );
        $broker->settleDone($this);

        return NULL;;
    }

    public function process($broker, $rawData)
    {
        if (
            !$this->checkHeader($broker, $this->queueId, self::PKG_QUEUE)
        )
            return false;

        $this->rawData = $rawData;
        // check quorum, response error if there is no quorum
        $quorum = 1;
        $tracers = array();
        $this->cmdPush_GetQuorum($broker, $quorum, $tracers, $this->chanel);
        if ($this->chanel !== '')
            $this->cmdPush_GetQuorum($broker, $quorum, $tracers, '');

        if ($quorum > count($tracers))
        {
            $broker->dReject($this,
                self::ERROR_NO_QUORUM_TO_PUSH_MESSAGE,
                "No quorum to push message, required $quorum, but found ".count($tracers)
            );
            return NULL;
        }

        if (isset($broker->pager[$this->queueId])) // generete message ID if generator defined
        {
            $this->header[self::PKG_GEN_ID] = ++$broker->pager[$this->queueId][self::RESP_CURRENT_ID];
        }
//echo "quorum $quorum\n";
        $this->pushQuorum = $quorum;
        $this->responseQuorum = $quorum;
        $this->traceCount = count($tracers);
        $broker->toBeSettle($this);

        $header = array(
            self::PKG_CHANEL => $this->chanel,
            self::PKG_CLIENT => $this->client->getId()
        );
        if ($this->id)
            $header[self::PKG_CID] = $this->id;

        foreach ($tracers as $subD)
            $subD->client->checkPush($broker, $subD, $header, $rawData);
        
        if ($this->pushQuorum == 0)
            $this->nofifySubscribers($broker);
    }

    public function responseArrived($broker, $mode, $rawData)
    {
        // if message akcnolaged by quorum send response to client with message ID
        if ($mode == self::RESP_OK)
            $this->pushQuorum--;

        if ($this->pushQuorum <= 0)
            $this->nofifySubscribers($broker);
    }
}
