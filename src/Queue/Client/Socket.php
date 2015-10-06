<?php

namespace Kalmyk\Queue\Client;

use \Kalmyk\Queue\StreamParser;
use Evenement\EventEmitter;
use \Kalmyk\Queue\QueueClientInterface;

class Socket
{
    public function __construct(EventEmitter $stream, QueueClientInterface $client)
    {
        $parser = new StreamParser();

        $client->setOnMessage(
            function ($message, $data) use ($stream, $parser)
            {
                $stream->write($parser->serialize($message, $data, true));
            }
        );

        $stream->on('data',
            function ($data) use ($parser, $client)
            {
                try {
                    $messages = $parser->parse($data, true);

                    foreach ($messages as $message)
                    {
                        $header = $message[0];
                        $data = isset($message[1])?$message[1]:NULL;
                        $client->receive($header, $data);
                    }
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

