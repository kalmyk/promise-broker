<?php

namespace Kalmyk\Worker;

class QueueStorage implements StorageInterface
{
    private $db = NULL;

    function __construct($db)
    {
        $this->db = $db; //new SQLite3("{$Config->data_folder}/$instance_name.msg.sqlite");
    }

    function func_set_rec($shard_id, $attr)
    {
        $s = $this->db->prepare(
"insert into msg_{$shard_id} values (
)");
        $s->bindValue(':mqid'
            ,array_get($attr,'mqid')
        );
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
    
    function set($header, $data)
    {
        switch ($funcname)
        {
            case 'set_rec':
                return $this->func_set_rec($shard_id, $attr);
            case 'clean':
                return $this->func_clean($shard_id, $attr);
            default:
                $result = "function not found $funcname ";
                echo $result;
                print_r($attr);
                return $result;
        }
    }
    
    function get($funcname, $shard_id, $attr)
    {
        switch ( $funcname )
        {
            case 'count':
                echo "count processed shard:$shard_id\n";
                return $this->db->querySingle("select count(*) from msg_{$shard_id}");
//                return $this->db->query("delete from msg_{$shard_id}");
            case 'select':
                return $this->func_select($shard_id, $attr);
            default:
                echo "unknown command $funcname:";
                print_r($attr);
                return NULL;
        }
    }
    
// this will create table with ROWID=PRIMARY KEY
// CREATE TABLE t(x INTEGER PRIMARY KEY ASC, y, z);
// SELECT rowid, x FROM t; to check

    public function createTables($queueId)
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS pg_{$queueId} ("
           ." `page_id` int,"       /* date */
           ." `msg_count` int,"
           ." `create_date` datetime,"
           ." PRIMARY KEY (`page_id`));"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS mq_{$queueId} ("
           ." `page_id` INTEGER,"
           ." `row_pos` int,"
           ." `custom_id` varchar(64),"
           ." `parent_page_id` int,"
           ." `parent_row_pos` int,"
           ." `msg_head` TEXT,"
           ." `msg_body` TEXT,"
           ." PRIMARY KEY (`page_id`, `row_pos`));"
        );
        return true;
    }

    public function removeTables()
    {
        echo "remove_shard {$this->shardId}\n";
    }
}
