<?php

namespace Toa\Queue\Client;

class QueueTask implements \Toa\Queue\QueueConst
{
    private $taskResponseCallback = NULL;
    private $cmd = NULL;            // the request content array
    private $data = NULL;
    private $chanel = NULL;
    private $isFinished = false;
    
    public function __construct($taskResponseCallback, $request, $data, $chanel)
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

    public function resolve($data, $chanel=NULL)
    {
        if (!$this->isFinished())
        {
            $this->isFinished = true;
            call_user_func($this->taskResponseCallback, $this, self::RESP_OK, $data, $chanel);
            $this->cmd = NULL;
        }
    }

    public function reject($data, $chanel=NULL)
    {
        if (!$this->isFinished())
        {
            $this->isFinished = true;
            call_user_func($this->taskResponseCallback, $this, self::RESP_ERROR, $data, $chanel);
            $this->cmd = NULL;
        }
    }

    public function progress($data, $chanel=NULL)
    {
        if (!$this->isFinished())
        {
            call_user_func($this->taskResponseCallback, $this, self::RESP_EMIT, $data, $chanel);
        }
    }
}
