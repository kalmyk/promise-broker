<?php

namespace Kalmyk\Worker;

class StorageQueueMsg implements StorageInterface
{
    private $db = NULL;

    function __construct($db)
    {
        $this->db = $db; //new SQLite3("{$Config->data_folder}/$instance_name.msg.sqlite");
    }

    function funcAddMsg(
        $queue,
        $page_date,
        $page_id,
        $row_pos,
        $parent_queue,
        $parent_page_date,
        $parent_page_id,
        $parent_row_pos,
        $custom_id,
        $msg_head,
        $msg_body
    )
    {
        $s = $this->db->prepare("insert into msg values (?,?,?,?,?,?,?,?,?,?,?)");
        $s->bindParam( 1, $queue,            SQLITE3_INTEGER);
        $s->bindParam( 2, $page_date,        SQLITE3_INTEGER);
        $s->bindParam( 3, $page_id,          SQLITE3_INTEGER);
        $s->bindParam( 4, $row_pos,          SQLITE3_INTEGER);
        $s->bindParam( 5, $parent_queue,     SQLITE3_INTEGER);
        $s->bindParam( 6, $parent_page_date, SQLITE3_INTEGER);
        $s->bindParam( 7, $parent_page_id,   SQLITE3_INTEGER);
        $s->bindParam( 8, $parent_row_pos,   SQLITE3_INTEGER);
        $s->bindParam( 9, $custom_id,        SQLITE3_TEXT);
        $s->bindParam(10, $msg_head,         SQLITE3_TEXT);
        $s->bindParam(11, $msg_body,         SQLITE3_TEXT);
        $s->execute();
    }

    function func_select($shard_id, $attr)
    {
        $s = "SELECT ".
            implode(",", $attr['fields']).
            " FROM msg_{$shard_id}".
            " WHERE ".implode(" AND ", $attr['where']).
            " ORDER BY ".implode(",", $attr['order by']).
            " LIMIT ".$attr['limit'];
        echo $s."\n";
        $res = array();
        $q = $this->db->query($s);
        while ($row = $q->fetchArray(SQLITE3_ASSOC)) {
            $res[] = $row;
        }
        return $res;
    }

    private function funcName($data)
    {
        if (isset($data['funcName']))
            return $data['funcName'];
        else
            throw new \Exception("Function name not found in message body");
    }

    function process($task)
    {
        try
        {
            $data = $task->getData();
            $funcName = $this->funcName($data);
            switch ($funcName)
            {
                case 'addMsg':
                    return $this->funcAddMsg();
                case 'clean':
                    return $this->func_clean($shard_id, $attr);
                default:
                    throw new \Exception("Unknown function $funcName");
            }
        }
        catch (\Exception $e)
        {
            $task->reject($e);
        }
    }

// this will create table with ROWID=PRIMARY KEY
// CREATE TABLE t(x INTEGER PRIMARY KEY ASC, y, z);
// SELECT rowid, x FROM t; to check

    public function createTables()
    {
// it is required to have link message to another in another queue
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS qpage ("
           ." `queue` int,"
           ." `page_date` int,"
           ." `page_id` int,"
//           ." `user_stream` int,"  it is good to recalculate stream on reload
           ." `create_date` datetime,"
           ." PRIMARY KEY (`page_date`, `page_id`, `queue`));"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS msg ("
           ." `queue` int,"
           ." `page_date` int,"
           ." `page_id` int,"
           ." `row_pos` int,"
           ." `parent_queue` int,"
           ." `parent_page_date` int,"
           ." `parent_page_id` int,"
           ." `parent_row_pos` int,"
           ." `custom_id` varchar(64),"
           ." `msg_head` TEXT,"
           ." `msg_body` TEXT,"
           ." PRIMARY KEY (`page_date`, `page_id`, `queue`, `row_pos`));"
        );
        return true;
    }

    public function removeTables()
    {
        echo "remove_shard {$this->shardId}\n";
    }
}
