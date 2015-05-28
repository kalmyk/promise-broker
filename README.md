promise-broker
==============

A message queue implementation based on the promise paradigm.

It means that order of server responses does not determined by order
of client requests. So, client application responsible to provide request
identifier that will be replied in the response.

Worker pattern:
```php
$worker->subscribe('job')->then(
    function ($response)
    {
        echo "Unsubscribed $response.\n";
    },
    function ($reason)
    {
        echo "ERROR: unable to subscribe, reason $reason\n";
    },
    function ($task)
    {
        echo "incoming task";
        var_dump($task->getData());
        $task->resolve(true);
    }
);
$worker->pull();
```

Client pattern:
```php
$client->call('job', array('data' => 123))->then(
    function ($response)
    {
        echo "response $response\n";
    }
);
```

What is it: this is server and client code that is done on pure PHP
Why PHP: server will be rewriten to C++ with protocol compatibility if idea is working.

