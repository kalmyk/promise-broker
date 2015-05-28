<?php

namespace Kalmyk\Worker;

class QueueStorage implements StorageInterface
{
    private $db = NULL;
    private $qualifier = NULL;
    private $shardId = NULL;

    function __construct($qualifier, $shardId)
    {
        $this->qualifier = $qualifier;
        $this->shardId = $shardId;

///        $this->db = new SQLite3("{$Config->data_folder}/$instance_name.msg.sqlite");
    }

    function func_set_rec($shard_id, $attr)
    {
        $s = $this->db->prepare(
"insert into msg_{$shard_id} values (
)");
        $s->bindValue(':mqid'                  ,array_get($attr,'mqid'));
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

    public function createTables($stream)
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS pg ("
           ." `id INTEGER PRIMARY KEY` autoincrement," 
           ." `id_segment` int,"    /* date */
           ." `id_sequince` int,"   /* mono sequence in segment */
           ." `queue` varchar(128),"
           ." `msg_count` int,"
           ." `create_date` datetime,"
           ." PRIMARY KEY (`id_segment`, `id_id`));"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS mq ("
           ." `page_id` INTEGER,"
           ." `row_pos` int,"
           ." `custom_id` varchar(64),"
           ." `parent_id_segment` int,"
           ." `parent_id_id` int,"
           ." `msg_head` TEXT,"
           ." `msg_body` TEXT,"
           ." PRIMARY KEY (`id_segment`, `id_id`));"
        );
        return true;
    }

    public function removeTables()
    {
        echo "remove_shard {$this->shardId}\n";
    }
}
