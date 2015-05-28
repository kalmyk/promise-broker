<?php

namespace Kalmyk\Queue\Server;

class CommandSettle extends CommandDeferred
{
    public function process($broker, $rawData)
    {
        if (!$this->checkHeader($broker, $mode, self::PKG_RESPONSE))
            return false;
echo "settle 1\n";
        $srcD = $broker->getSettle($this->header[self::PKG_CLIENT], $this->header[self::PKG_CID]);
        if ($srcD)
        {
echo "settle 2\n";
            $srcD->responseArrived($broker, $mode, $rawData);
            $broker->dResolve($this, NULL);
        }
        else
        {
echo "settle 3\n";
            $broker->dReject($this,
                self::ERROR_SETTLE_NOT_FOUND,
                "The settle requester not found"
            );
        }
    }
}

