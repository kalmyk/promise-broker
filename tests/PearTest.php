<?php

use \Kalmyk\Queue\StreamParser;
use \Kalmyk\Queue\Server\PromiseBroker;

class PearTest extends \QueueTests\BrokerTestBase
{
    private $master = NULL;
    private $slave = NULL;

    private $worker = NULL;
    private $server = NULL;

    public function setUp()
    {
        $this->master = new PromiseBroker('MASTER');
        $this->slave = new PromiseBroker('SLAVE');

        $this->client = $this->connectClient('Master', $this->master, 'Client', $this->clientState);
        $this->worker = $this->connectClient('Slave', $this->slave, 'Worker', $this->workerState);
        $this->linkBroker($this->master, $this->slave);
    }

    function testPearRemoteCall()
    {
        $this->markTestSkipped('multy broker is not ready.');
        $this->flushStreams();

        $result_sub = NULL;
        $worker_calls = 0;
        $arrived_data = NULL;

        $this->client->subscribe('customer')->then(
            function ($response) use (&$result_sub)
            {
                $result_sub = 'done';
            },
            function ($reason)
            {
                $this->assertFalse(true, 'no error expected');
            },
            function ($task) use (&$worker_calls, &$arrived_data)
            {
                $worker_calls++;
                $arrived_data = $task->getData();
                $task->resolve('customer.read.result');
            }
        );
        $this->client->pull();
        $this->flushStreams();
        
        $data = array('data1' => 1, 'data2' => 2);
        $result_call = NULL;
        $this->worker->call('customer', $data)->then(
            function ($response) use (&$result_call)
            {
                $result_call = $response;
            }
        );
        $this->flushStreams();
        
        $this->assertEquals(1, $worker_calls, 'incorrect number of worker calls');
        $this->assertEquals('customer.read.result', $result_call);
        $this->assertEquals($data, $arrived_data);

        $result_unsub = NULL;
        $this->client->unSub('customer')->then(
            function ($response) use (&$result_unsub)
            {
                $result_unsub = 'done';
            }
        );
        $this->flushStreams();
        
        $this->assertEquals('done', $result_sub);
        $this->assertEquals('done', $result_unsub);
        $this->worker->stopPull();
    }
}
