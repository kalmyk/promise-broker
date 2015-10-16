<?php

namespace Kalmyk\Queue;

use \React\Promise\Deferred;

class QueueClientBase implements QueueConst, QueueClientInterface
{
    private $onMessage = NULL;
    private $commandId = 0;
    private $cmdList = array();
    private $simultaneousTaskLimit = 0;
    private $tasksRequested = 0;

    public function getPendingCmdCount()
    {
        return count($this->cmdList);
    }

    public function setOnMessage(callable $onMessage)
    {
        if (isset($this->onMessage))
            throw new \Exception('unable to redeclare message callback');

        $this->onMessage = $onMessage;
    }

    private function sendWithNoResponse($command)
    {
        call_user_func(
            $this->onMessage,
            $command,
            NULL
        );
    }

    protected function sendCommand($command, $data, $stream = '')
    {
        $this->commandId++;
        $command[self::PKG_ID] = $this->commandId;

        if ($stream)
        {
            // TODO: streaming request
            // $command[] = create worker stream
        }
        $deferred = new Deferred();
        $this->cmdList[$this->commandId] = $deferred;
        call_user_func(
            $this->onMessage,
            $command,
            $data
        );
        return $deferred->promise();
    }

    public function sendTaskResponse($request, $responseMode, $data, $chanel)
    {
        if (self::RESP_OK == $responseMode || self::RESP_ERROR == $responseMode)
        {
            $this->tasksRequested--;
            $this->checkPopTask(false);
        }
        if (!isset($request[self::PKG_CID])) // no response needed
            return false;

        $header = array(
            self::PKG_CMD => self::CMD_SETTLE,
            self::PKG_CLIENT => $request[self::PKG_CLIENT],
            self::PKG_CID => $request[self::PKG_CID],
            self::PKG_RESPONSE => $responseMode
        );
        if (NULL !== $chanel)
            $header[self::PKG_CHANEL] = $chanel;

        call_user_func($this->onMessage, $header, $data);
    }

    // data could be array, task or request
    private function settle($deferred, $response, $data)
    {
        $mode = isset($response[self::PKG_RESPONSE]) ? $response[self::PKG_RESPONSE] : '';
        switch ($mode)
        {
        case self::RESP_OK:
            $deferred->resolve($data);
            return true;

        case self::RESP_ERROR:
            $deferred->reject($data);
            return true;

        case self::RESP_EMIT:
            if (isset($response[self::PKG_CLIENT]))
            {
                $task = new QueueTask(
                    array($this, 'sendTaskResponse'),
                    $response,
                    $data
                );
                $deferred->progress($task);
                $this->checkPopTask(true);
            }
            else
            {
                $deferred->progress($data);
            }
            return false;

        default:
            $deferred->reject(NULL);
            return true;
        }
    }

    public function pull()
    {
        $this->simultaneousTaskLimit++;
        $this->checkPopTask(false);
    }

    public function stopPull()
    {
        $this->simultaneousTaskLimit--;
        $this->checkPopTask(false);
    }

    private function checkPopTask($getMoreThanOneTask)
    {
        $delta = $this->simultaneousTaskLimit - $this->tasksRequested;
        if ($delta > 0 || ($getMoreThanOneTask && $delta > 1))
        {
            $this->tasksRequested++;
            $this->sendWithNoResponse(
                array(self::PKG_CMD => self::CMD_POP)
            );
        }
        else if ($delta < 0)
        {
            $this->tasksRequested--;
            $this->sendWithNoResponse(
                array(self::PKG_CMD => self::CMD_UNPOP)
            );
        }
    }

    public function receive($cmd, $data)
    {
        if (isset($cmd[self::PKG_ID]) && isset($this->cmdList[$cmd[self::PKG_ID]]))
        {
            $wait = $this->cmdList[$cmd[self::PKG_ID]];
            if ($this->settle($wait, $cmd, $data))
            {
                unset($this->cmdList[$cmd[self::PKG_ID]]);
            }
        }
        else
        {
//print_r($data);
            // unknown command ID arrived, nothing to do, could write PHP error?
        }
    }

    public function close()
    {
        //TODO: implement close connection
        echo "close connection\n";
    }
}

