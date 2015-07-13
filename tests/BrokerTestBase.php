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
    protected function connectClient($srvName, PromiseBroker $server, $cliName, &$clientState)
    {
        // the Network!
        $cliSocket = new NullStream();
        $srvSocket = new NullStream();
        $this->streams[] = $cliSocket;
        $this->streams[] = $srvSocket;
        $cliSocket->setStream($srvSocket);
        $srvSocket->setStream($cliSocket);

        // Listen data on the Queue Server
        $clientState = new ClientState($server);
        $srvSocket->setOnReceive(
            function ($data) use ($srvName, $server, $clientState, $cliName)
            {
                $this->assertTrue(is_array($data), 'protocol error, array of strings expected!');
                foreach ($data as $line)
                {
//echo "$cliName > $srvName $line\n";
//file_put_contents('/tmp/q.log', "$cliName > $srvName $line\n", FILE_APPEND);
                    $this->assertTrue(is_string($line), 'protocol error, string line expected!');
                }
                $server->process($data, $clientState);
            }
        );
        $clientState->setOnMessage(
            function ($message) use ($srvSocket)
            {
                $srvSocket->send($message);
            }
        );

        // Client or Worker
        $cli = new QueueClient();
        $cliSocket->setOnReceive(
            function ($data) use ($srvName, $cli, $cliName)
            {
                $this->assertTrue(is_array($data), 'protocol error, array of strings expected!');
                foreach ($data as $line)
                {
//echo "$srvName > $cliName $line\n";
//file_put_contents('/tmp/q.log', "$cliName < $srvName $line\n", FILE_APPEND);
                    $this->assertTrue(is_string($line), 'protocol error, string line expected!');
                }
                $cli->receive($data);
            }
        );
        $cli->setOnMessage(
            function ($data) use ($cliSocket)
            {
                $cliSocket->send($data);
            }
        );
        return $cli;
    }

    protected function linkBroker(PromiseBroker $master, PromiseBroker $slave)
    {
        // the Network!
        $cliSocket = new NullStream();
        $srvSocket = new NullStream();
        $this->streams[] = $cliSocket;
        $this->streams[] = $srvSocket;
        $cliSocket->setStream($srvSocket);
        $srvSocket->setStream($cliSocket);

        // Listen data on the Queue Server
        $clientState = new ClientState($master);
        $srvSocket->setOnReceive(
            function ($data) use ($master, $clientState)
            {
                $this->assertTrue(is_array($data), 'protocol error, array of strings expected!');
                foreach ($data as $line)
                {
//echo "Slave > Master $line\n";
                    $this->assertTrue(is_string($line), 'protocol error, string line expected!');
                }
                $master->process($data, $clientState);
            }
        );
        $clientState->setOnMessage(
            function ($message) use ($srvSocket)
            {
                $srvSocket->send($message);
            }
        );

        // Client or Worker
        $cli = new ServerState($slave);
        $cliSocket->setOnReceive(
            function ($data) use ($cli, $slave)
            {
                $this->assertTrue(is_array($data));
                foreach ($data as $line)
                {
//echo "Master > Slave $line\n";
                    $this->assertTrue(is_string($line), 'protocol error');
                }
                $slave->process($data, $cli);
            }
        );
        $cli->setOnMessage(
            function ($data) use ($cliSocket)
            {
                $cliSocket->send($data);
            }
        );
        $cli->connect();
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
