<?php

namespace Toa\Queue\Server;

class ClientState implements \Toa\Queue\QueueConst
{
    private $clientId = NULL;
    private $popState = 0;
    private $onMessage = NULL;

    /**
        subscribtion commands
            [$id] => CommandBaseSub
    */
    private $sub = array();

    /**
        subscribtion and trace commands in priority order
            [$id] => CommandBaseSub
    */
    private $subAndTrace = array();

    /**
        pending PUSH comands
            [] => deferred
    */
    private $pushStack = array();

    public function __construct(PromiseBroker $queue)
    {
        $this->clientId = $queue->attachClient($this);
    }

    function onMessage(callable $onMessage)
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
    }

    public function addTrace($subD)
    {
        $this->sub[$subD->id] = $subD;
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
            $this->pushStack[] = array($subD, $header, $rawData);
        }
    }

    public function processPendingPush($broker)
    {
        if (count($this->pushStack) == 0)
            return false;
        
        list($subD, $header, $rawData) = array_shift($this->pushStack);
        $broker->dSettle(
            $subD,
            self::RESP_EMIT,
            $rawData,
            $header
        );
        return true;
    }
    
    public function send($message)
    {
        call_user_func($this->onMessage, $message, '' /* stream */);
    }
}
