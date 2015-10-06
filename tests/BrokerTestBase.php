<?php

namespace QueueTests;

use \Kalmyk\Queue\Server\PromiseBroker;
use \Kalmyk\Queue\Server\ClientState;
use \Kalmyk\Queue\Client\QueueClient;
use \Kalmyk\Queue\Server\ServerState;
use \Kalmyk\Queue\NullStream;

class BrokerTestBase extends \PHPUnit_Framework_TestCase
{
    private $streams = array();

    // return QueueClient
    protected function connectClient($srvName, PromiseBroker $broker, $cliName, &$clientState)
    {
        // the Network!
        $cliSocket = new NullStream(true);
        $srvSocket = new NullStream(false);
        $this->streams[] = $cliSocket;
        $this->streams[] = $srvSocket;
        $cliSocket->setStream($srvSocket);
        $srvSocket->setStream($cliSocket);

        // Listen data on the Queue Server
        $clientState = new ClientState($broker);
        $srvSocket->setOnReceive(
            function ($message) use ($srvName, $broker, $clientState, $cliName)
            {
//echo "$cliName > $srvName $line\n";
//file_put_contents('/tmp/q.log', "$cliName > $srvName $line\n", FILE_APPEND);
                $broker->process($message[0], isset($message[1])?$message[1]:NULL, $clientState);
            }
        );
        $clientState->setOnSendMessage(
            function ($header, $data, $doEncodeData) use ($srvSocket)
            {
                $srvSocket->send($header, $data, $doEncodeData);
            }
        );

        // Client or Worker
        $cli = new QueueClient();
        $cliSocket->setOnReceive(
            function ($message) use ($srvName, $cli, $cliName)
            {
//echo "$srvName > $cliName $line\n";
//file_put_contents('/tmp/q.log', "$cliName < $srvName $line\n", FILE_APPEND);
                $cli->receive($message[0], isset($message[1])?$message[1]:NULL);
            }
        );
        $cli->setOnMessage(
            function ($header, $data) use ($cliSocket)
            {
                $cliSocket->send($header, $data, true);
            }
        );
        return $cli;
    }

    protected function linkBroker(PromiseBroker $master, PromiseBroker $slave)
    {
        // the Network!
        $cliSocket = new NullStream(false);
        $srvSocket = new NullStream(false);
        $this->streams[] = $cliSocket;
        $this->streams[] = $srvSocket;
        $cliSocket->setStream($srvSocket);
        $srvSocket->setStream($cliSocket);

        // Listen data on the Queue Server
        $clientState = new ClientState($master);
        $srvSocket->setOnReceive(
            function ($data) use ($master, $clientState)
            {
//echo "Slave > Master $line\n";
                $master->process($data, $clientState);
            }
        );
        $clientState->setOnSendMessage(
            function ($header, $data, $doEncodeData) use ($srvSocket)
            {
                $srvSocket->send($header, $data, $doEncodeData);
            }
        );

        // Client or Worker
        $cli = new ServerState($slave);
        $cliSocket->setOnReceive(
            function ($data) use ($cli, $slave)
            {
                $this->assertTrue(is_array($data));
//echo "Master > Slave $line\n";
                $slave->process($data, $cli);
            }
        );
        $cli->setOnSendMessage(
            function ($header, $data, $doEncodeData) use ($cliSocket)
            {
                $cliSocket->send($header, $data, $doEncodeData);
            }
        );
//TODO:        $cli->connect();
        return $cli;
    }

    protected function flushStreams()
    {
        do
        {
            $found = false;
            foreach ($this->streams as $stream)
                $found = $found || $stream->processBuffer();
        }
        while ($found);
    }
}
