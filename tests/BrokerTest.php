<?php

namespace QueueTests;

use \Kalmyk\Queue\Client\QueueClient;
use \Kalmyk\Queue\Client\Command\Call;
use \Kalmyk\Queue\Client\Command\Marker;
use \Kalmyk\Queue\Client\Command\Subscribe;
use \Kalmyk\Queue\Client\Command\Trace;
use \Kalmyk\Queue\Server\PromiseBroker;

class BrokerTest extends \QueueTests\BrokerTestBase
{
    private $client = NULL;
    private $worker = NULL;
    private $server = NULL;

    private $clientState = NULL;
    private $workerState = NULL;

    public function setUp()
    {
        $this->server = new PromiseBroker('SERVER');
        $this->client = $this->connectClient('S', $this->server, 'C', $this->clientState);
        $this->worker = $this->connectClient('S', $this->server, 'W', $this->workerState);
    }

    public function tearDown()
    {
        if ($this->client)
        {
            $this->flushStreams();
            $this->assertFalse($this->clientState->getPopState(), 'client pop state has to be empty');
            $this->client->pull();
            $this->flushStreams();
            $this->assertTrue($this->clientState->getPopState(), 'client is not ready to work after pull');

            $this->assertEquals(0, $this->client->getPendingCmdCount(), 'client stack not empty');
        }

        if ($this->worker)
        {
            $this->flushStreams();
            $this->assertFalse($this->workerState->getPopState(), 'worker pop state has to be empty');
            $this->worker->pull();
            $this->flushStreams();
            $this->assertTrue($this->workerState->getPopState(), 'worker is not ready to work after pull');

            $this->assertEquals(0, $this->worker->getPendingCmdCount(), 'worker stack not empty');
        }
        
//        $this->client->disconnect();
//        $this->worker->disconnect();
        
//        $this->server->checkEmpty();
    }

    function testEcho()
    {
        $data = array('data1' => 1, 'data2' => 2);

        $result_echo = NULL;
        $this->client->getEcho($data)->then(
            function ($response) use (&$result_echo)
            {
                $result_echo = $response;
            }
        );

        $this->flushStreams();

        $this->assertEquals($data, $result_echo, 'echo should return the send data');
    }

    function testNoSubscriberInPush()
    {
        $result_call = 'no call invoked';
        $fail_reason = NULL;
        $fail_arrived = NULL;
        $result_emit = 'no emit invoked';

        $this->client->call('customer', NULL)->then(
            function ($response) use (&$result_call)
            {
                $result_call = 'done';
            },
            function ($reason) use (&$fail_reason, &$fail_arrived)
            {
                $fail_arrived = 'fail arrived';
                $fail_reason = $reason;
            },
            function ($status) use (&$result_emit)
            {
                $result_emit = 'done';
            }
        );
        $this->flushStreams();
        $this->assertEquals('no call invoked', $result_call);
        $this->assertEquals('no emit invoked', $result_emit);
        $this->assertEquals('fail arrived', $fail_arrived);
        $this->assertEquals(QueueClient::ERROR_NO_QUEUE_FOUND, $fail_reason[QueueClient::RESP_ERROR_CODE]);
    }

    function testRemoteCall()
    {
        $result_sub = NULL;
        $worker_calls = 0;
        $arrived_data = NULL;

        $this->worker->subscribe('customer')->then(
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
        $this->worker->pull();
        $this->flushStreams();
        
        $data = array('data1' => 1, 'data2' => 2);
        $result_call = NULL;
        $this->client->call('customer', $data)->then(
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
        $this->worker->unSub('customer')->then(
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

    function testDuplicateSubscription()
    {
        $result_sub1 = NULL;
        $result_sub2 = 'no sub2 expected';
        $fail_sub2_reason = NULL;

        $this->worker->pull();
        $this->worker->subscribe('customer')->then(
            function ($response) use (&$result_sub1)
            {
                $result_sub1 = 'queue 1 unsubscribed';
            },
            function ($reason)
            {
            },
            function ($task)
            {
                $task->resolve('sub1.taskResult');
            }
        );

        $this->worker->subscribe('customer')->then(
            function ($response) use (&$result_sub2)
            {
                $result_sub2 = 'done';
            },
            function ($reason) use (&$fail_sub2_reason)
            {
                $fail_sub2_reason = $reason;
            },
            function ($task)
            {
            }
        );
        $this->flushStreams();

        $this->assertEquals('no sub2 expected', $result_sub2);
        $this->assertEquals(QueueClient::ERROR_ALREADY_SUBSCRIBED, $fail_sub2_reason[QueueClient::RESP_ERROR_CODE]);

        $result_trace = 'no trace expected';
        $fail_trace_reason = NULL;
        $this->worker->trace('customer', 1)->then(
            function ($response) use (&$result_trace)
            {
                $result_trace = 'done';
            },
            function ($reason) use (&$fail_trace_reason)
            {
                $fail_trace_reason = $reason;
            },
            function ($task)
            {
            }
        );
        $this->flushStreams();

        $this->assertEquals('no trace expected', $result_trace);
        $this->assertEquals(QueueClient::ERROR_ALREADY_SUBSCRIBED, $fail_trace_reason[QueueClient::RESP_ERROR_CODE]);

        $result_call = NULL;
        $this->client->call('customer', NULL)->then(
            function ($response) use (&$result_call)
            {
                $result_call = $response;
            }
        );
        $this->flushStreams();
        $this->assertEquals('sub1.taskResult', $result_call, 'check that call finds free Queue immediately');

        $this->assertEquals(NULL, $result_sub1, 'no result delivered before unsubscribe');

        $fail_unsub_reason2 = NULL;
        $result_unsub1 = NULL;
        $result_unsub2 = 'no unsub2 expected';
        $this->worker->unSub('customer')->then(
            function ($response) use (&$result_unsub1)
            {
                $result_unsub1 = 'queue 1 unsubscribed';
            }
        );

        $this->worker->unSub('customer')->then(
            function ($response) use (&$result_unsub2)
            {
                $result_unsub2 = 'done';
            },
            function ($reason) use (&$fail_unsub2, &$fail_unsub_reason2)
            {
                $fail_unsub_reason2 = $reason;
                $fail_sub2 = 'sub2 failed';
            }
        );
        $this->flushStreams();

        $this->assertEquals('queue 1 unsubscribed', $result_sub1);
        $this->assertEquals('no unsub2 expected', $result_unsub2);
        $this->assertEquals(QueueClient::ERROR_NO_QUEUE_FOUND, $fail_unsub_reason2[QueueClient::RESP_ERROR_CODE]);
        $this->assertEquals(0, $this->client->getPendingCmdCount(), 'empty client stack');
        $this->assertEquals(0, $this->worker->getPendingCmdCount(), 'empty worker stack');
        
        $result_call = NULL;
        $fail_call = NULL;
        $fail_call_reason = NULL;
        $this->client->call('customer', NULL)->then(
            function ($response) use (&$result_call)
            {
                $result_call = 'response';
            },
            function ($reason) use (&$fail_call, &$fail_call_reason)
            {
                $fail_call_reason = $reason;
                $fail_call = 'call failed';
            }
        );
        $this->flushStreams();

        $this->assertEquals(NULL, $result_call, 'check that queue is not handled');
        $this->assertEquals('call failed', $fail_call);
        $this->assertEquals(QueueClient::ERROR_NO_QUEUE_FOUND, $fail_call_reason[QueueClient::RESP_ERROR_CODE]);
        $this->worker->stopPull();
    }

    function testPopUnPop()
    {
        $worker_calls = 0;
        $result_call = 0;
        $task_list = array();

        $this->worker->pull();
        $this->worker->subscribe('customer')->then(
            NULL,
            NULL,
            function ($task) use (&$worker_calls, &$task_list)
            {
                $worker_calls++;
                $task_list[] = $task;
            }
        );
        $this->flushStreams();
        
        for ($i = 1; $i <= 7; $i++)
        {
            $this->client->call('customer', NULL)->then(
                function ($response) use (&$result_call)
                {
                    $result_call += $response;
                }
            );
            $this->flushStreams();
            $this->assertEquals(0, $result_call, 'no result returned');
            $this->assertEquals(1, $worker_calls, 'one active task in queue');
        }
        $this->flushStreams();

        $i = 1;
        $sum = 0;
        while (count($task_list) > 0)
        {
            $sum += $i;
            $this->assertEquals($i, $worker_calls, 'one active task in queue');
            $task = array_pop($task_list);

            $task->resolve($i);
            $this->flushStreams();

            $this->assertEquals($sum, $result_call, 'results delivered');
            $i++;
        }

        $this->worker->unSub('customer');
        $this->worker->stopPull();
    }

    function testPublishNoListener()
    {
        $result_call = 'not delivered';
        $error_call = NULL;
        $worker_state = '';
        $this->client->publish('customer', 'msg')->then(
            function ($response) use (&$result_call)
            {
                $result_call = $response;
            },
            function ($reason) use (&$error_call)
            {
                $error_call = $reason;
            }
        );
        $this->flushStreams();
        $this->assertEquals(0, $result_call, 'no clients to delivery');
        $this->assertEquals(NULL, $error_call);
    }

    function testPublishToClient()
    {
        $result_call = NULL;
        $worker_state = '';
        $this->worker->subscribe('customer')->then(
            NULL,
            NULL,
            function ($task) use (&$worker_state)
            {
                $worker_state .= $task->getData();
                $task->resolve(NULL);
            }
        );
        $this->worker->pull();
        
        $this->client->subscribe('customer')->then(
            NULL,
            NULL,
            function ($task) use (&$worker_state)
            {
                $worker_state .= $task->getData();
                $task->resolve(NULL);
            }
        );
        $this->client->pull();
        $this->flushStreams();
        
        $this->client->publish('customer', 'msg')->then(
            function ($response) use (&$result_call)
            {
                $result_call = $response;
            }
        );
        $this->flushStreams();
        $this->assertEquals(2, $result_call, 'two clients');
        $this->assertEquals('msgmsg', $worker_state, 'two notifies');

        $this->client->publish('customer', 'msg')->then(
            function ($response) use (&$result_call)
            {
                $result_call = $response;
            }
        );
        $this->flushStreams();

        $this->assertEquals(2, $result_call, 'two clients');
        $this->assertEquals('msgmsgmsgmsg', $worker_state, 'two notifies');

        $this->worker->stopPull();
        $this->client->stopPull();
        $this->worker->unSub('customer');
        $this->client->unSub('customer');
    }

    function testMarker()
    {
        // error is returned if queue marker is not defined
        $error_arrived = NULL;
        $this->client->marker('customer')->then(
            function ($response) {},
            function ($reason) use (&$error_arrived)
            {
                $error_arrived = $reason;
            }
        );
        $this->flushStreams();
        $this->assertEquals(QueueClient::ERROR_MARK_GENERATOR_NOT_FOUND, $error_arrived[QueueClient::RESP_ERROR_CODE]);

        // error is returned if queue marker is not defined but clients tries to init incorrect
        $error_arrived = NULL;
        $this->client->marker('customer', 'segment_not_exists', 'new_segment', 33)->then(
            function ($response) {},
            function ($reason) use (&$error_arrived)
            {
                $error_arrived = $reason;
            }
        );
        $this->flushStreams();
        $this->assertEquals(QueueClient::ERROR_INCORRECT_MARKER_SEGMENT, $error_arrived[QueueClient::RESP_ERROR_CODE]);

        // correct init
        $response_arrived = NULL;
        $this->client->marker('customer', '', 'new_segment', 33)->then(
            function ($response) use (&$response_arrived)
            {
                $response_arrived = $response;
            }
        );
        $this->flushStreams();
        $this->assertEquals(array('segment' => 'new_segment', 'current_id' => 33), $response_arrived);

        // incorrect existing alter
        $error_arrived = NULL;
        $this->client->marker('customer', 'segment_not_exists', 'new_segment', 55)->then(
            function ($response) {},
            function ($reason) use (&$error_arrived)
            {
                $error_arrived = $reason;
            }
        );
        $this->flushStreams();
        $this->assertEquals(QueueClient::ERROR_INCORRECT_MARKER_SEGMENT, $error_arrived[QueueClient::RESP_ERROR_CODE]);

        // correct existing alter
        $response_arrived = NULL;
        $this->client->marker('customer', 'new_segment', 'new_segment2', 77)->then(
            function ($response) use (&$response_arrived)
            {
                $response_arrived = $response;
            }
        );
        $this->flushStreams();
        $this->assertEquals(array('segment' => 'new_segment2', 'current_id' => 77), $response_arrived);

        // generator to zero
        $response_arrived = NULL;
        $this->client->marker('customer', 'new_segment2', 'segment_changed')->then(
            function ($response) use (&$response_arrived)
            {
                $response_arrived = $response;
            }
        );
        $this->flushStreams();
        $this->assertEquals(array('segment' => 'segment_changed', 'current_id' => 0), $response_arrived);
    }

    function testPushFailed()
    {
        $error_arrived = NULL;
        $this->client->push('customer', 1, 'chanel')->then(
            NULL,
            function ($reason) use (&$error_arrived)
            {
                $error_arrived = $reason;
            }
        );
        $this->flushStreams();
        $this->assertEquals(QueueClient::ERROR_NO_QUORUM_TO_PUSH_MESSAGE, $error_arrived[QueueClient::RESP_ERROR_CODE]);

        $this->client->trace('customer', 1, 'chanel')->then(
            NULL,
            NULL,
            function ($task)
            {
                $task->resolve(true);
            }
        );
        $this->client->pull();

        $error_arrived = NULL;
        $this->client->push('customer', 1, 'chanel_not_exists')->then(
            NULL,
            function ($reason) use (&$error_arrived)
            {
                $error_arrived = $reason;
            }
        );
        $this->flushStreams();
        $this->assertEquals(QueueClient::ERROR_NO_QUORUM_TO_PUSH_MESSAGE, $error_arrived[QueueClient::RESP_ERROR_CODE]);
        $this->client->stopPull();
        $this->client->unTrace('customer', 'chanel');
    }

    function testTrace()
    {
        $read_error_arrived = NULL;
        $trace = '';
        $this->worker->trace('customer', 1)->then(
            function ($response)
            {
            },
            function ($reason) use (&$read_error_arrived)
            {
                $read_error_arrived = 'error arrived';
            },
            function ($task) use (&$trace)
            {
                $trace = 
                    $task->chanel().'.'.
                    $task->markId().'.'.
                    $task->markSegment();
                $task->resolve(true);
            }
        );
        $this->worker->marker('customer', '', 'new_segment', 22);
        $this->worker->pull();
        $this->flushStreams();

        $sub = '';
        $this->client->subscribe('customer')->then(NULL,NULL,
            function ($task) use (&$sub)
            {
                $sub = 
                    $task->chanel().'.'.
                    $task->markId().'.'.
                    $task->markSegment();
                $task->resolve(true);
            }
        );

        $error_arrived = NULL;
        $response_arrived = NULL;
        $this->client->push('customer', 1, 'chanel_not_exists')->then(
            function ($response) use (&$response_arrived)
            {
                $response_arrived = $response;
            },
            function ($reason) use (&$error_arrived)
            {
                $error_arrived = $reason;
            }
        );
        $this->client->pull();
        $this->flushStreams();

        $this->assertEquals('chanel_not_exists.23.new_segment', $trace);
        $this->assertEquals('chanel_not_exists.23.new_segment', $sub);
        $this->assertEquals(array('quorum'=>1,'send'=>2), $response_arrived);

        $this->worker->unTrace('customer');
        $this->client->unSub('customer');
        $this->worker->stopPull();
        $this->client->stopPull();
    }

    function testScenario()
    {
        $read_error_arrived = NULL;
        $this->worker->subscribe('db')->then(
            NULL, NULL,
            function ($task)
            {
                $a = $task->getData();
                $a['db'] = 'loaded';
                $task->resolve($a);
            }
        );

        $this->worker->subscribe('cache')->then(
            NULL, NULL,
            function ($task)
            {
                $a = $task->getData();
                $a['cache'] = 'loaded';
                $task->resolve($a);
            }
        );
        $this->worker->pull();
        $this->flushStreams();

        $error_arrived = NULL;
        $response_arrived = NULL;

        $c = new Call('cache');
        $c->then(
            new Call('db')
        );
        
        $this->client->send($c, array('id' => 1))->then(
            function ($response) use (&$response_arrived)
            {
                $response_arrived = $response;
            }
        );
        $this->flushStreams();
 
        $this->assertEquals(array('id' => 1,'cache'=>'loaded','db'=>'loaded'), $response_arrived);

        $this->worker->unSub('cache');
        $this->worker->unSub('db');
        $this->worker->stopPull();
    }

    function testSuspendResume()
    {
        $log = '';
        $this->worker->trace('push', 1)->then(NULL,NULL,
            function ($task) use (&$log)
            {
                $task->resolve(true);
                $log .= "\n".$task->getData();
            }
        );
        $this->worker->subscribe('publish')->then(NULL,NULL,
            function ($task) use (&$log)
            {
                if (strpos($task->getData(), 'pub.'))
                    $log .= "\n".$task->getData();
                else
                    $log .= "\n".$task->getData();
                $task->resolve(true);
            }
        );
        $this->flushStreams();
        
        $this->client->publish('publish', 'pub.1');
        $this->client->call('publish', 'call.value');
        $this->client->publish('publish', 'pub.2');
        $this->client->push('push', 'push.1');
        $this->client->publish('publish', 'pub.3');
        $this->client->push('push', 'push.2');
        $this->client->publish('publish', 'pub.4');
        $this->client->push('push', 'push.3');
        $this->client->publish('publish', 'pub.5');
        $this->flushStreams();

        $this->worker->pull();
        $this->flushStreams();

        $this->assertEquals(
            "\npush.1\npush.2\npush.3\npub.1\npub.2\npub.3\npub.4\npub.5\ncall.value",
            $log,
            'work order push/pub/call'
        );

        $this->worker->unSub('publish');
        $this->worker->unTrace('push');
        $this->worker->stopPull();
    }

    function testStorageMode()
    {
        // start remote storage, it is ready to provide history
        $cur_storage_id = 12;
        $this->worker->marker('customer', '', 'worker_page', $cur_storage_id);
        $this->worker->subscribe('customer.storage')->then(NULL,NULL,
            function ($task)
            {
                $filter = $task->getData();
                $task->progress(array($filter['from']+1=>'record '.($filter['from']+1)));
                $task->progress(array($filter['to']=>'record '.$filter['to']));
                $task->resolve('all found');
            }
        );
        $this->worker->pull();
        $this->flushStreams();

        // start second storage that revovery state from existing
        $storage_db = array(
            array( '10' => 'record 10' )
        );
        $storage_last_id = 10;
    
        $cmd = new Marker('customer');
        $cmd->then(
            new Trace('customer', 1)
        );
        
        $cur = NULL;
        $this->client->send($cmd, NULL)->then(
            NULL,
            NULL,
            function ($task) use (&$storage_db, &$cur)
            {
                if (is_object($task))
                {
                    $storage_db[] = $task->getData();
                    $task->resolve(true);
                }
                else
                {
                    $cur = $task;
                }
            }
        );
        $this->flushStreams();

        // concurent write
        $this->worker->push('customer', array('13' => 'record 13'));
        $this->worker->push('customer', array('14' => 'record 14'));

        $this->client->call('customer.storage', array(
            'func' => 'history',
            'from' => $storage_last_id,  // 10
            'to' => $cur['current_id']   // 12
        ))->then(
            function ($response)
            {
                // echo "recovery done\n";
            },
            function ($reason)
            {
                echo "recovery failed\n";
            },
            function ($data) use (&$storage_db)
            {
                $storage_db[] = $data;
            }
        );
        $this->flushStreams();

        $this->client->pull();
        $this->flushStreams();

        $this->worker->push('customer', array('15' => 'record 15'));
        $this->flushStreams();

        $result = array(
            array('10' => 'record 10'),
            array('11' => 'record 11'),
            array('12' => 'record 12'),
            array('13' => 'record 13'),
            array('14' => 'record 14'),
            array('15' => 'record 15')
        );

        $this->assertEquals($result, $storage_db, 'history database order is not valid');

        $this->worker->stopPull();
        $this->client->stopPull();
        $this->client->unTrace('customer');
        $this->worker->unSub('customer.storage');
    }

    function testReadLoginFailed()
    {
        $result_sub = NULL;
        $this->client->subscribe('customer'/*, ['login' => 'profile'] */)->then(
            function ($response) use (&$result_sub)
            {
                $result_sub = $response;
            },
            function ($reason)
            {
                $this->assert('no error expected');
            },
            function ($task)
            {
                $task->resolve('customer.read.result');
            }
        );
        $this->client->unSub('customer');
   }
}
