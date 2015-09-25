<?php

namespace Kalmyk\Queue\Server;

class ClientState implements \Kalmyk\Queue\QueueConst
{
    private $clientId = NULL;
    private $popState = 0;
    private $onMessage = NULL;

    /**
        subscribtion commands
            [scheme][$id] => DeferBaseSub
    */
    private $sub = array();

    /**
        subscribtion and trace commands in priority order
            [scheme][$id] => DeferBaseSub
    */
    private $subAndTrace = array();

    public function __construct(PromiseBroker $broker)
    {
        $this->clientId = $broker->attachClient($this);
    }

    function setOnMessage(callable $onMessage)
    {
        $this->onMessage = $onMessage;
    }

    function getId()
    {
        return $this->clientId;
    }

    public function getPopState()
    {
        return $this->popState > 0;
    }

    public function addPopState()
    {
        return $this->popState++;
    }

    public function delPopState()
    {
        return $this->popState--;
    }

    public function getAllSubscriptions()
    {
        return $this->sub;
    }

    public function addSubscription($subD)
    {
        $this->sub[$subD->id] = $subD;
        $this->subAndTrace[$subD->id] = $subD;
    }

    public function addTrace($subD)
    {
        $this->subAndTrace[$subD->id] = $subD;
    }

    public function delSub($subD)
    {
        unset($this->sub[$subD->id]);
        unset($this->subAndTrace[$subD->id]);
    }

    public function checkPush($broker, $subD, $header, $rawData)
    {
        if ($this->getPopState())
        {
            $broker->dSettle(
                $subD,
                self::RESP_EMIT,
                $rawData,
                $header
            );
        }
        else
        {
            $subD->pushStack($header, $rawData);
        }
    }

    public function processPendingPush($broker)
    {
        foreach ($this->subAndTrace as $subD)
        {
            while ($this->getPopState() && $stack = $subD->popStack())
            {
                list($header, $rawData) = $stack;
                $broker->dSettle(
                    $subD,
                    self::RESP_EMIT,
                    $rawData,
                    $header
                );
            }
        }
        return true;
    }
    
    public function send($message)
    {
        call_user_func($this->onMessage, $message, '' /* stream */);
    }
}
