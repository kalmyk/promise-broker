<?php

namespace Kalmyk\Queue\Server;

class DeferMarker extends DeferBase
{
    private function pageResponseOk($broker, $queueId)
    {
        $body = array(
            self::RESP_SEGMENT => $broker->pager[$queueId][self::RESP_SEGMENT],
            self::RESP_CURRENT_ID => $broker->pager[$queueId][self::RESP_CURRENT_ID]
        );
        $broker->dSettle($this, self::RESP_EMIT, $body, true);
        $broker->dResolve($this, $body);
    }

    public function process($broker, $rawData)
    {
        if (
            !$this->checkHeader($broker, $queueId, self::PKG_QUEUE)
        )
            return false;

        if (isset($this->header[self::PKG_SEGMENT])
         && isset($this->header[self::PKG_NEW_SEGMENT])
        )
        {
            $curSegment = isset($broker->pager[$queueId]) ? $broker->pager[$queueId][self::RESP_SEGMENT] : '';

            if ($curSegment !== $this->header[self::PKG_SEGMENT])
            {
                // client need to be updated with new prefix value
                $header = array(self::RESP_SEGMENT => $curSegment);
                if (isset($broker->pager[$queueId]))
                    $header[self::RESP_CURRENT_ID] = $broker->pager[$queueId][self::RESP_CURRENT_ID];

                $broker->dReject($this,
                    self::ERROR_INCORRECT_MARKER_SEGMENT,
                    "Incorrect marker prefix speciffied '{$this->header[self::PKG_SEGMENT]}'",
                    $header
                );
                return NULL;
            }
            // if client knows current segment then he is able to alter segment & generator
            $broker->pager[$queueId][self::RESP_SEGMENT] = $this->header[self::PKG_NEW_SEGMENT];
            if (isset($this->header[self::PKG_GEN_ID]))
                $broker->pager[$queueId][self::RESP_CURRENT_ID] = (int)$this->header[self::PKG_GEN_ID];
            else
                $broker->pager[$queueId][self::RESP_CURRENT_ID] = 0;

            $this->pageResponseOk($broker, $queueId);
        }
        else
        {
            if (isset($broker->pager[$queueId]))
            {
                $this->pageResponseOk($broker, $queueId);
            }
            else
            {
                $broker->dReject($this,
                    self::ERROR_MARK_GENERATOR_NOT_FOUND,
                    "Mark generator not found for '$queueId'"
                );
            }
            return NULL;
        }
    }
}
