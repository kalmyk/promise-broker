<?php

namespace Kalmyk\Worker;

interface StorageInterface
{
    public function __construct($qualifier, $shardId);
    public function doTask($task);
    public function connect();
    public function createTables();
    public function removeTables();
}
