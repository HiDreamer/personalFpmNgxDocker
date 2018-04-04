<?php
abstract class storage_MysqlBaseModel
{
    protected $cur_conn = null;
    private $DB_CONF = 'CardSale';
    private $DB_TABLES = 'CS_TABLES';
    
    protected $class_name = '';

    private function getIns() //由类名确定表名
    {/*{{{*/
        static $conn_arr = array();
        $db_conf = ConfigYaf::getConf($this->DB_CONF);
        $db_index = md5(serialize($db_conf));

        if(!isset($conn_arr[$db_index]))
        {
            $this->cur_conn = new PDOLib($db_conf['host'], $db_conf['database'], $db_conf['user'], $db_conf['passwd'], $db_conf['port']);
            $conn_arr[$db_index] = $this->cur_conn;
        }
        else
        {
            $this->cur_conn = $conn_arr[$db_index];
        }

        $all_tables = ConfigYaf::getConf($this->DB_TABLES);
        $this->table = !empty($this->class_name) && isset($all_tables[$this->class_name]) ? $all_tables[$this->class_name] : 'wrong_table_name';
    }/*}}}*/

    public function deleteByCond($cond_arr)
    {/*{{{*/
        $this->getIns();
        $where = !empty($cond_arr) ? "where " . $this->buildConds($cond_arr) : '';
        $sql = "delete from {$this->table} {$where} ;";
        $rows = $this->cur_conn->getAffectRow($sql);
        return $rows;
    }/*}}}*/
    
    public function insertByArr($pairs)
    {/*{{{*/
        $this->getIns();
        $fields = array_keys($pairs);
        $values = array_values($pairs);
        $insert_str = "insert into {$this->table} set ";
        $glue = '';
        foreach($fields as $field)
        {
            $insert_str .= "{$glue}{$field}=?";
            $glue = ', ';
        }
        $insert_str .= ';';
        return $this->cur_conn->getAffectRow($insert_str, $values);
    }/*}}}*/
    
    public function updateByCond($pairs, $conds)
    {/*{{{*/
        $this->getIns();
        $fields = array_keys($pairs);
        $values = array_values($pairs);
        $update_str = "update {$this->table} set ";
        $glue = '';
        foreach($fields as $field)
        {
            $update_str .= "{$glue}{$field}=?";
            $glue = ', ';
        }
        $where = !empty($conds) ? "where " . $this->buildConds($conds) : '';
        $update_str .= " {$where};";
        return $this->cur_conn->getAffectRow($update_str, $values);
    }/*}}}*/

    public function queryByCond($conds, $fields = array(), $for_update = false, $all = false)
    {/*{{{*/
        $this->getIns();
        $field_str = empty($fields) ? "* " : implode(', ', $fields);
        $where = !empty($conds) ? "where " . $this->buildConds($conds) : '';
        $query_str = "select {$field_str} from {$this->table} {$where}";
        $query_str .= $for_update ? " for update;" : ";"; 
        $res = $this->cur_conn->query($query_str, array(), $all);
        return $res;
    }/*}}}*/

    public function queryCount($conds = array())
    {/*{{{*/
        $this->getIns();
        $where = !empty($conds) ? "where " . $this->buildConds($conds) : '';
        $query_str = "select count(*) as cnt from {$this->table} {$where};";
        $res = $this->cur_conn->query($query_str);
        return isset($res['cnt']) ? $res['cnt'] : 0;
    }/*}}}*/
    
    //默认返回影响的行数
    public function queryByCmd($cmd, $get_rows = true)
    {/*{{{*/
        $this->getIns();
        $cmd = str_replace('table_name', $this->table, $cmd);//将table_name 换为真正的$this->table
        //如果get_rows 为true 默认返回影响的行数, 否则返回所有查询结果
        return $get_rows ? $this->cur_conn->getAffectRow($cmd, array()) : $this->cur_conn->query($cmd, array(), true);
    }/*}}}*/
    
    public function getList($conds, $fields = array(), $orders = array(), $limits = array())
    {/*{{{*/
        $this->getIns();
        $field_str =  empty($fields) ? "* " : implode(', ', $fields);
        $where = !empty($conds) ? "where " . $this->buildConds($conds) : ''; 
        $order_str = $this->buildOrders($orders);
        $limit_str = !empty($limits) ? "  limit {$limits['skip']},{$limits['limit']}" : '';
        
        $query_str = "select {$field_str} from {$this->table} {$where} {$order_str} {$limit_str};";
        $res = $this->cur_conn->query($query_str, array(), true);
        return $res;
    }/*}}}*/

    public function getLastNewId()
    {/*{{{*/
        return $this->cur_conn->getLastInsertID();
    }/*}}}*/

    public function beginTran()
    {/*{{{*/
        $this->getIns();
       $this->cur_conn->beginTran(); 
    }/*}}}*/

    public function rollback()
    {/*{{{*/
        $this->getIns();
        $this->cur_conn->rollback();
    }/*}}}*/

    public function commit()
    {/*{{{*/
        $this->getIns();
        $this->cur_conn->commit();
    }/*}}}*/

    private function buildOrders($orders)
    {/*{{{*/
        $order_arr = array();
        foreach($orders as $ind => $order)
        {
            $order_arr[] = "$ind $order";
        }         
        return "order by ".implode(', ', $order_arr);
    }/*}}}*/
    
    private function buildConds($cond_arr)
    {/*{{{*/
        list($key, $val) = each($cond_arr);
        if(in_array($key, array('and', 'or')))
        {
            $sql_arr = array();
            foreach($val as $sub_val)
            {
                $sql_arr[] = "(" . $this->buildConds($sub_val) . ")";
            }
            return  implode(" $key ", $sql_arr);
        }
        list($field, $value) = each($val);
        if(in_array($key , array('<', '>' , '>=', '<=' , '=', 'like', '!=')))
        {
            return "$field  $key  '$value'";
        }
        if($key == 'FIND_IN_SET')
        {
            return "FIND_IN_SET('{$value}', {$field})";
        }
        if($key == 'IN' || $key == 'NOT IN')
        {
            $arr = array();
            foreach($value as $sub_val) 
            {
                $arr[] = "'{$sub_val}'";
            }
            return "{$field} {$key} (". implode(',', $arr) .")";
        }
        if(empty($cond_arr))
        {
            return '';
        }        
    }/*}}}*/

  protected  abstract function idName();
  protected  abstract function tableScheme();
}
