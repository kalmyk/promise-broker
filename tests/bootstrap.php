<?php

$loader = @include __DIR__.'/../vendor/autoload.php';

date_default_timezone_set('UTC');

    $command = sprintf(
        'php src/server.php %s >/dev/null 2>&1 & echo $!',
//        'php src/server.php %s >/tmp/q.log 2>&1 & echo $!',
        QUEUE_SERVER_PORT
    );

    // Execute the command and store the process ID
    $output = array();
    exec($command, $output);
    $pid = (int) $output[0];

    echo sprintf(
        '%s - Queue server started on %s:%d with PID %d',
        date('r'),
        QUEUE_SERVER_HOST,
        QUEUE_SERVER_PORT,
        $pid
    ) . PHP_EOL;

    // Kill the web server when the process ends
    register_shutdown_function(function() use ($pid) {
        echo sprintf('%s - Killing process with ID %d', date('r'), $pid) . PHP_EOL;
        exec('kill ' . $pid);
    });

