<?php

namespace Kalmyk\Queue;

class QueueTask implements QueueConst
{
    private $taskResponseCallback = NULL;
    private $cmd = NULL;            // the request content array
    private $data = NULL;
    private $isFinished = false;

    public function __construct($taskResponseCallback, $request, $data)
    {
        $this->taskResponseCallback = $taskResponseCallback;
        $this->cmd = $request;
        $this->data = $data;
    }

    public function isFinished()
    {
        return $this->isFinished;
    }

    public function responseNedded()
    {
        return isset($this->cmd[self::PKG_CID]);
    }

    // SETTLE command, that sends result from worker to queue,
    // could find destination cliend by client id and request id
    public function getClientId()
    {
        return $this->cmd[self::PKG_CLIENT];
    }

    public function getId()
    {
        return $this->cmd[self::PKG_CID];
    }

    public function getData()
    {
        return $this->data;
    }

    public function chanel()
    {
        return $this->cmd[self::PKG_CHANEL];
    }

    public function markId()
    {
        return isset($this->cmd[self::PKG_GEN_ID]) ? $this->cmd[self::PKG_GEN_ID] : 0;
    }

    public function markSegment()
    {
        return isset($this->cmd[self::PKG_SEGMENT]) ? $this->cmd[self::PKG_SEGMENT] : '';
    }

    public function resolve($result, $chanel=NULL)
    {
        if (!$this->isFinished())
        {
            $this->isFinished = true;
            call_user_func($this->taskResponseCallback, $this, self::RESP_OK, $result, $chanel);
            $this->cmd = NULL;
        }
    }

    public function reject($reason, $chanel=NULL)
    {
        if (!$this->isFinished())
        {
            $this->isFinished = true;
            call_user_func($this->taskResponseCallback, $this, self::RESP_ERROR, $reason, $chanel);
            $this->cmd = NULL;
        }
    }

    public function progress($status, $chanel=NULL)
    {
        if (!$this->isFinished())
        {
            call_user_func($this->taskResponseCallback, $this, self::RESP_EMIT, $status, $chanel);
        }
    }
}
