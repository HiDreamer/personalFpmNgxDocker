<?php

class PDOLib
{

    private $_db_conn = null;
    private $_encode = 'utf8';
    private $_client_code = 'binary';
    private $_persistent = false;

    private $_err_type = 'PDO';
    private $_start_time = ''; //每次操作的起始时间
    private $_allow_time = '200'; //允许时长,超过即超时

    public function __construct($host, $db_name, $user, $passwd, $port = '3306')
    {/*{{{*/
        $set_oper = 'SET NAMES '. $this->_encode . ';' . 'SET CHARACTER_SET_CLIENT=' . $this->_client_code .';';
        try{
            $this->_db_conn = new PDO("mysql:host={$host};port={$port};dbname={$db_name}", $user, $passwd, array(PDO::MYSQL_ATTR_INIT_COMMAND => $set_oper, PDO::ATTR_PERSISTENT => $this->_persistent));

        }
        catch(PDOException $e)
        {
            $err_msg = array(
                'err' => 'create object fail when connect',
                'msg' => $e->getMessage(),
            );
            $this->logger($err_msg);
            return;
        }
    }/*}}}*/
    
    public function query($sql, $vals = array(), $all = false) //默认取出一条结果
    {/*{{{*/
        $res = false;
        try{
            $sth = $this->_db_conn->prepare($sql);
            $ind = 0;
            foreach($vals as $val)
            {
                $sth->bindValue(++$ind, $val);
            }
            $this->_start_time = microtime(true);
            //执行预处理语句
            if($sth->execute())
            {
                $this->isTimeOut($sql);
                $res = $all ? $sth->fetchAll(PDO::FETCH_ASSOC) : $sth->fetch(PDO::FETCH_ASSOC);
            }
            else
            {
                $err_msg = array(
                    'err' => 'return false after execute QUERY',
                    'msg' => 'errorcode: ' . $sth->errorCode() .' ,errInfo:' . print_r($sth->errorInfo(), true),
                    'sql' => $sql,
                    'val' => print_r($vals, true),
                );
                $this->logger($err_msg);
            }
        }
        catch(PDOException $e)
        {
            $err_msg = array(
                'err' => 'error occured when execute QUERY',
                'msg' => $e->getMessage(),
                'sql' => $sql,
                'val' => print_r($vals, true),
            );
            $this->logger($err_msg);
        }
        return $res;
    }/*}}}*/
    
    public function getAffectRow($sql, $vals = array())
    {/*{{{*/
        $res = false;
        try
        {
            $sth = $this->_db_conn->prepare($sql);
            $ind = 0;
            foreach($vals as $val)
            {
                $sth->bindValue(++$ind, $val);//bindValue默认从1开始                
            }
            $this->_start_time = microtime(true);
            //执行预处理语句
            if($sth->execute())
            {
                $this->isTimeOut($sql);
                $res = $sth->rowCount();
            }
            else
            {
                $err_msg = array(
                    'err' => 'return false after execute GETAFFECTROW',
                    'msg' => 'errorcode: ' . $sth->errorCode() .' ,errInfo:' . print_r($sth->errorInfo(), true),
                    'sql' => $sql,
                    'val' => print_r($vals, true),
                );
                $this->logger($err_msg);
            }
        }
        catch(PDOException $e)
        {
            $err_msg = array(
                'err' => 'error occured when execute GETAFFECTROW',
                'msg' => $e->getMessage(),
                'sql' => $sql,
                'val' => print_r($vals, true),
            );
            $this->logger($err_msg);
        }
        return $res;
    }/*}}}*/
    
    public function getLastInsertID()
    {/*{{{*/
        $id = (int) $this->_db_conn->lastInsertId();
        return $id;
    }/*}}}*/
    
    public function autoCommit()
    {
        $this->_db_conn->setAttribute(PDO::ATTR_AUTOCOMMIT, true);
    }

    public function beginTran()
    {
       $this->_db_conn->beginTransaction();
    }

    public function rollback()
    {
        $this->_db_conn->rollback();
    }

    public function commit()
    {
       $this->_db_conn->commit();
    }

    private function isTimeOut($sql_str)
    {/*{{{*/
        $end_time = microtime(true);
        $time_span = ($end_time - $this->_start_time)*1000;
        if($time_span > $this->_allow_time)            
        {
            $err_info = array(
                'err' => 'execute sql command in too long time',
                'sql' => $sql_str,
            );
            $this->logger($err_info, 'timeout');
        } 
    }/*}}}*/

    private function logger($err_msg, $level = 'error') //还可以为timeout
    {/*{{{*/
        $err_info = '';
        if(is_array($err_msg))
        {
            $err_info = print_r(json_encode($err_msg), true);          
        }
        $logger = new ObjectLogger($this->_err_type);
        $logger->writeLog($err_info, $level);
    }/*}}}*/
}
