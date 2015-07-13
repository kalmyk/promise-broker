<?php

namespace Kalmyk\Queue\Client;

use Kalmyk\Queue\StreamParser;
use Evenement\EventEmitter;

class Socket
{
    public function __construct(EventEmitter $stream, QueueClientInterface $client)
    {
        $parser = new StreamParser();

        $client->setOnMessage(
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
                    $client->close();
                    return;
                }
            }
        );

        $stream->on('close',
            function () use ($client)
            {
                $client->close(); // is it working?
            }
        );
    }
}

