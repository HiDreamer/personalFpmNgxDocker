<?php
    /**
     * 单例模式，实例化类
     */
    class ObjectFinder
    {
        private $_objs = array();
        private static $instance = null;
        private static function getInstance()
        {
            if(is_null(self::$instance))
            {
                self::$instance = new self();
            }        
        }
        
        private function __construct()
        {
        }

        private function findImple($class_name)
        {
            if(!isset($this->_objs[$class_name]))
            {
                $this->_objs[$class_name] = new $class_name;
            }   
            return $this->_objs[$class_name];
        }

        public static function find($class_name)
        {
            self::getInstance();            
            return self::$instance->findImple($class_name);
        }
    }
