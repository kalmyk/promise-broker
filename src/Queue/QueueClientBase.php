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

    private function sendWithNoResponse(QueueCommandBase $obj)
    {
        call_user_func(
            $this->onMessage,
            array(
                json_encode($obj->getCommandData(
                    array($this, 'sendTaskResponse'),
                    array($this, 'checkPopTask')
                ))
            ),
            ''  /* stream data */
        );
    }

    public function send(QueueCommandBase $obj, $data, $stream = '')
    {
        $this->commandId++;
        $command = $obj->getCommandData();
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
            array(
                json_encode($command),
                json_encode($data)
            ),
            ''  /* stream data */
        );
        return $deferred->promise();
    }

    public function sendTaskResponse(QueueTask $task, $responseMode, $data, $chanel)
    {
        if ($task->isFinished())
        {
            $this->tasksRequested--;
            $this->checkPopTask(false);
        }
        if (!$task->responseNedded())
            return false;

        $cmd = array(
            array(
                self::PKG_CMD => self::CMD_SETTLE,
                self::PKG_CLIENT => $task->getClientId(),
                self::PKG_CID => $task->getId(),
                self::PKG_RESPONSE => $responseMode
            ),
            $data
        );
        if (NULL !== $chanel)
            $cmd[0][self::PKG_CHANEL] = $chanel;

        foreach ($cmd as &$p)
            $p = json_encode($p);

        call_user_func($this->onMessage, $cmd, '');
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
                new QueueCommandBase(
                    array(self::PKG_CMD => self::CMD_POP)
                )
            );
        }
        else if ($delta < 0)
        {
            $this->tasksRequested--;
            $this->sendWithNoResponse(
                new QueueCommandBase(
                    array(self::PKG_CMD => self::CMD_UNPOP)
                )
            );
        }
    }

    public function receive($data)
    {
        $cmd = json_decode($data[0], true);
        if (isset($cmd[self::PKG_ID]) && isset($this->cmdList[$cmd[self::PKG_ID]]))
        {
            $wait = $this->cmdList[$cmd[self::PKG_ID]];
            if ($this->settle($wait, $cmd, json_decode($data[1], true)))
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
