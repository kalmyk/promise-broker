<?php

namespace Kalmyk\Queue\Server;

class PromiseBroker implements \Kalmyk\Queue\QueueConst
{
    /**
        Subscribed Workewrs for queues
            [scheme][queueId][chanelId][clientId] => subD
    */
    private $wSub = array();

    /**
        storage chanel listeners
            [scheme][queueId][chanelId][clientId] => subD
    */
    private $wTrace = array();

    /**
        generator
            [scheme][queueId] => array(
                RESP_CURRENT_ID => INT
                RESP_PREFIX => 'the page number'
                RESP_SEGMENT => generator page
            )

    */
    public $pager = array();

    /**
        CALL commands that are waiting for the free worker
            [scheme][$queueId][chanelId][] = deferred
    */
    private $qCall   = array();

    /**
        reqest has been sent to worker/writer, client is waiting for the SETTLE
        CALL/PUSH
            [$clientId][$commandId] = deferred
    */
    private $qSettle = array();

    /**
        List of connect Clients
            [$clientId] => ClientSocket;
    */
    public $client  = array();

    private $pearServer = array();  // pearServer

    private $genClientId = 0; 
    private $stack = NULL; // current command stack
    private $id = '';

    public function __construct($id = '')
    {
        $this->id = $id;
    }

    public function attachClient(ClientState $client)
    {
        $this->genClientId++;
        $this->client[$this->genClientId] = $client;
        return $this->genClientId;
    }
    
    public function detachClient(ClientState $client)
    {
        $clientId = $client->getId();
        unset($this->client[$clientId]);
        // TODO: clean subs
        return NULL;
    }
    
    public function attachPear(ClientState $pearServer)
    {
        $this->pearServer[] = $pearServer;
    }

    public function confirmRepl($d)
    {
        $a = array();
        foreach ($this->pearServer as $repl)
        {
            $d->sendToServer($repl);
//            $a[] = $repl->confirm($d);
        }
/*        return \React\Promise\map($a,
            function ()
            {
                echo "map callback\n";
            }
        );*/
    }
    
    public function getSub($queueId, $chanelId, $clientId)
    {
        return
            isset($this->wSub[$queueId][$chanelId][$clientId]) ?
                $this->wSub[$queueId][$chanelId][$clientId] :
                NULL;
    }

    public function getSubStack($queueId, $chanelId)
    {
        return
            isset($this->wSub[$queueId][$chanelId]) ?
                $this->wSub[$queueId][$chanelId] :
                array();
    }

    public function addSub($subD)
    {
        $this->wSub[$subD->queue][$subD->chanel][$subD->client->getId()] = $subD;

        $subD->client->addSubscription($subD);
        $this->checkWaitTask($subD->client);
/*
        if (remotes exists)
        if (no remote registered)
        $this->rSub[$subD->queue][$subD->chanel] = 
            new Command\Subscrioption
            send(command)
*/
        $this->confirmRepl($subD);
    }

    public function removeSub($client, $subD)
    {
        $client->delSub($subD);

        unset($this->wSub[$subD->queue][$subD->chanel][$client->getId()]);

        if (count($this->wSub[$subD->queue][$subD->chanel]) == 0)
            unset($this->wSub[$subD->queue][$subD->chanel]);

        if (count($this->wSub[$subD->queue]) == 0)
            unset($this->wSub[$subD->queue]);
    }

    public function getTrace($queueId, $chanelId, $clientId)
    {
        return
            isset($this->wTrace[$queueId][$chanelId][$clientId]) ?
                $this->wTrace[$queueId][$chanelId][$clientId] :
                NULL;
    }

    public function getTraceStack($queueId, $chanelId)
    {
        return
            isset($this->wTrace[$queueId][$chanelId]) ?
                $this->wTrace[$queueId][$chanelId] :
                array();
    }

    public function addTrace($subD)
    {
    //TODO: [$subD->scheme]
        $this->wTrace[$subD->queue][$subD->chanel][$subD->client->getId()] = $subD;
        $subD->client->addTrace($subD);
    }

    public function removeTrace($client, $subD)
    {
        $client->delSub($subD);

        unset($this->wTrace[$subD->queue][$subD->chanel][$client->getId()]);

        if (count($this->wTrace[$subD->queue][$subD->chanel]) == 0)
            unset($this->wTrace[$subD->queue][$subD->chanel]);

        if (count($this->wTrace[$subD->queue]) == 0)
            unset($this->wTrace[$subD->queue]);
    }

    public function waitForResolver($queueId, $d)
    {
        $this->qCall[$queueId][$d->chanel][] = $d;
    }

    public function toBeSettle($d)
    {
        if (isset($this->qSettle[$d->client->getId()][$d->id]))
        {
            $this->dReject($d,
                self::ERROR_ALREADY_QUEUED,
                    "Command already queued"
            );
            return false;
        }
        $this->qSettle[$d->client->getId()][$d->id] = $d;
        return true;
    }

    public function getSettle($clientId, $id)
    {
        if (isset($this->qSettle[$clientId][$id]))
            return $this->qSettle[$clientId][$id];
        else
            return NULL;
    }

    public function settleDone($d)
    {
        $clientId = $d->client->getId();
        $id = $d->id;

        if (isset($this->qSettle[$clientId]))
        {
            unset($this->qSettle[$clientId][$id]);
            if (0 == count($this->qSettle[$clientId]))
                unset($this->qSettle[$clientId]);
        }
    }

    public function callWorker($queueId, $taskD, $subD)
    {
        if (!$this->toBeSettle($taskD))
            return false;

        $header = array(
            self::PKG_CHANEL => $taskD->chanel,
            self::PKG_CLIENT => $taskD->client->getId()
        );
        if ($taskD->id)
            $header[self::PKG_CID] = $taskD->id;

        $this->dSettle(
            $subD,
            self::RESP_EMIT,
            $taskD->data,
            $header
        );

        // move queue back to stack
        if (count($this->wSub[$queueId][$taskD->chanel]) > 0)
        {
            $workerId = $subD->client->getId();
            $content = $this->wSub[$queueId][$taskD->chanel][$workerId];
            unset($this->wSub[$queueId][$taskD->chanel][$workerId]);
            $this->wSub[$queueId][$taskD->chanel][$workerId] = $content;
        }
    }

    public function checkWaitTask($worker)
    {
        $worker->processPendingPush($this);
        $found = false;
        if ($worker->getPopState())
        {
            $q = $worker->getAllSubscriptions();
            foreach ($q as $subD)
                if (isset($this->qCall[$subD->queue][$subD->chanel]))
                {
                    $taskD = array_shift($this->qCall[$subD->queue][$subD->chanel]);
                    
                    if (count($this->qCall[$subD->queue][$subD->chanel]) == 0)
                        unset($this->qCall[$subD->queue][$subD->chanel]);
                    if (count($this->qCall[$subD->queue]) == 0)
                        unset($this->qCall[$subD->queue]);
                    
                    $this->callWorker($subD->queue, $taskD, $subD);
                    $found = true;
                }
                if (!$worker->getPopState())
                    return $found;
        }
        return $found;
    }

    public function dResolve($d, $data)
    {
        $this->dSettle($d, self::RESP_OK, json_encode($data));
    }

    public function dReject($d, $errorCode, $message, $body=array())
    {
        $body[self::RESP_ERROR_CODE] = $errorCode;
        $body[self::RESP_ERROR_MSG]  = $message;
        $this->dSettle($d, self::RESP_ERROR, json_encode($body));
    }

    public function dSettle($d, $mode, $rawData, $header = array())
    {
        $newTaskHeader = $d->settle($mode, $rawData, $header);
        if ($newTaskHeader)
            array_push(
                $this->stack, 
                array(
                    $d->client,
                    $newTaskHeader, 
                    $rawData
                )
            );
    }

    public function createPromise($cmd, $client)
    {
        switch ($cmd[self::PKG_CMD])
        {
        case self::CMD_POP:     return new DeferPop       ($cmd, $client);
        case self::CMD_SETTLE:  return new DeferSettle    ($cmd, $client);

        case self::CMD_CALL:    return new DeferCall      ($cmd, $client);
        case self::CMD_PUSH:    return new DeferPush      ($cmd, $client);

        case self::CMD_ECHO:    return new DeferEcho      ($cmd, $client);

        case self::CMD_SUB:     return new DeferSub       ($cmd, $client);
        case self::CMD_TRACE:   return new DeferTrace     ($cmd, $client);

        case self::CMD_UNSUB:   return new DeferUnSub     ($cmd, $client);
        case self::CMD_UNTRACE: return new DeferUnTrace   ($cmd, $client);
        case self::CMD_UNPOP:   return new DeferUnPop     ($cmd, $client);
        case self::CMD_MARKER:  return new DeferMarker    ($cmd, $client);

        default:
            throw new \Exception("function not found ".$cmd[self::PKG_CMD]);
        }
    }

    public function process($cmd, ClientState $client)
    {
// print_r($cmd);
        $this->stack = array(
            array(
                $client,
                json_decode($cmd[0], true),
                isset($cmd[1])?$cmd[1]:NULL
            )
        );
        while (count($this->stack) > 0)
        {
            list($fromClient, $header, $rawData) = array_shift($this->stack);
            $d = $this->createPromise($header, $fromClient);
            $d->process($this, $rawData);
        }
    }
}
