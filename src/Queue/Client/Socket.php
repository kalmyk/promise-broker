<?php

namespace Toa\Queue\Client;

use Toa\Queue\StreamParser;
use Evenement\EventEmitter;

class Socket
{
    public function __construct(EventEmitter $stream, $client)
    {
        $parser = new StreamParser();
        
        $client->onMessage(
            function ($message, $data) use ($stream, $parser)
            {
                $stream->write($parser->serialize($message, $data));
            }
        );
        
        $stream->on('data',
            function ($data) use ($parser, $client)
            {
                try {
                    $messages = $parser->parse($data);

                    foreach ($messages as $message)
                        $client->receive($message);
                }
                catch (\Exception $e) {
                    $connection->close();
                    return;
                }

            }
        );
    }
}

