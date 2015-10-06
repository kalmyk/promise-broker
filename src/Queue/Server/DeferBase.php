<?php

namespace Kalmyk\Queue\Server;

class DeferBase implements \Kalmyk\Queue\QueueConst
{
    public $client = NULL;
    protected $header = NULL;

    public $id = NULL;
    public $cmd = NULL;
    public $chanel = NULL;

    private $stack;

    public function __construct($header, $client)
    {
        $this->header = $header;
        $this->id = isset($header[self::PKG_ID]) ? $header[self::PKG_ID] : false;
        $this->cmd = $header[self::PKG_CMD];
        $this->chanel = isset($header[self::PKG_CHANEL]) ? $header[self::PKG_CHANEL] : '';
        $this->stack = isset($header[self::PKG_STACK]) ? $header[self::PKG_STACK] : NULL;
        $this->client = $client;
    }

    public function settle($mode, $data, $doEncodeData, $header)
    {
        if (isset($this->stack[$mode]) && isset($this->stack[$mode][self::PKG_CMD]))
        {
            $c = $this->stack[$mode];
            $header[self::PKG_CMD] = $c[self::PKG_CMD];

            if (isset($this->header[self::PKG_ID]))
                $header[self::PKG_ID] = $this->header[self::PKG_ID];

            if (isset($c[self::PKG_STACK]))
                $header[self::PKG_STACK] = $c[self::PKG_STACK];

            if (isset($c[self::PKG_QUEUE]))
                $header[self::PKG_QUEUE] = $c[self::PKG_QUEUE];

            if (isset($c[self::PKG_QUORUM]))
                $header[self::PKG_QUORUM] = $c[self::PKG_QUORUM];

            if ($mode == self::RESP_EMIT)
            {
                $result = new Deferred($this->stack[$mode]);
                throw new \Exception('new ID not implemented');
                return $result;
            }
            return $header;
        }
        else
        {
            if ($this->id || $mode == self::RESP_ERROR)
            {
                $header[self::PKG_RESPONSE] = $mode;
                if ($this->id)
                    $header[self::PKG_ID] = $this->id;

                $this->sendToClient($mode, $header, $data, $doEncodeData);
            }
            return NULL;
        }
    }

    public function getLevel()
    {
        return isset($this->header[self::PKG_LEVEL]) ? $this->header[self::PKG_LEVEL] : 0;
    }

    protected function sendToClient($mode, $header, $data, $doEncodeData)
    {
        $this->client->sendMessage($header, $data, $doEncodeData);
    }

    public function isFinished()
    {
        return isset($this->header[self::PKG_RESPONSE]);
    }

    public function checkHeader($broker, &$place, $index)
    {
        if (isset($this->header[$index]))
        {
            $place = $this->header[$index];
            return true;
        }

        $broker->dReject($this,
            self::ERROR_HEADER_IS_NOT_COMPLETED,
            "Header is not completed '$index'"
        );
        return false;
    }

    public function originClientId()
    {
        return isset($this->header[self::PKG_CLIENT]) ? $this->header[self::PKG_CLIENT] : '';
    }

    public function originRequestId()
    {
        return isset($this->header[self::PKG_CID]) ? $this->header[self::PKG_CID] : '';
    }
}

