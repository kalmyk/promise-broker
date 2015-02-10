<?php

namespace Toa\Queue\Server;

class CommandPear extends CommandDeferred
{
    public function process($broker, $rawData)
    {
/*        if ($this->client->pearServer)
        {
            $broker->dReject($this,
                self::ERROR_ALREADY_PEAR,
                "Client is already in pear mode."
            );
        }*/
        $broker->attachPear($this->client);
    }
}
