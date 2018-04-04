<?php
    class ObjectLogger
    {
        private $log_file = '';

        public function __construct($log_type)
        {
            $this->getLogFile($log_type);    
        }

        private function getLogFile($log_type)
        {
            $this->log_file = ConfigYaf::getConf("LOG_PATH") . "/{$log_type}-" . date("Ymd"); 
        }
        
        public function writeLog($err_msg, $emergency = "normal") //normal notice error exception timeout
        {
            $err_msg = "[ {$emergency} ], [". date("Y-m-d H:i:s"). " ], " . "[ err_detail: {$err_msg} ] " . "\r\n";
            error_log($err_msg, 3, $this->log_file);   
        }
    }
