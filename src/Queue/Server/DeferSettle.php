<?php

namespace Kalmyk\Queue\Server;

class DeferSettle extends DeferBase
{
    public function process($broker, $rawData)
    {
        if (!$this->checkHeader($broker, $mode, self::PKG_RESPONSE))
            return false;
        $srcD = $broker->getSettle($this->header[self::PKG_CLIENT], $this->header[self::PKG_CID]);
        if ($srcD)
        {
            $srcD->responseArrived($broker, $mode, $rawData);
            $broker->dResolve($this, NULL);
        }
        else
        {
            $broker->dReject($this,
                self::ERROR_SETTLE_NOT_FOUND,
                "The settle requester not found"
            );
        }
    }
}

