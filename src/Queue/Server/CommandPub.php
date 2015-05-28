<?php

namespace Kalmyk\Queue\Server;

class CommandPub extends CommandDeferred
{
    private $queue = NULL;

    private function notify($broker, $chanel, $header, $rawData)
    {
        $queue = $broker->getSubStack($this->queue, $chanel);
        foreach ($queue as $clientId => $subD)
            $subD->client->checkPush($broker, $subD, $header, $rawData);

        return count($queue);
    }

    public function process($broker, $rawData)
    {
        if (!$this->checkHeader($broker, $this->queue, self::PKG_QUEUE))
            return false;

        $header = array(
            self::PKG_CHANEL => $this->chanel,
            self::PKG_CLIENT => $this->client->getId()
        );

        $sends = $this->notify($broker, $this->chanel, $header, $rawData);
        if ($this->chanel !== '')
            $sends += $this->notify($broker, '', $header, $rawData);

        $broker->dResolve($this, $sends);
    }
}
