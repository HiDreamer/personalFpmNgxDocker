<?php
class ConfigYaf
{
    const CANT_GET_CONFIG_FILE_LOG_PATH = "/home/www/web_logs/cardsale_logs/read_config_err.log";
    #$common = new Yaf_Config_Ini (APPLICATION_PATH . '/conf/server/common.ini', 'common'); 
    #$config = Yaf_Application::app ()->getConfig ();
    public static function getConf($name)
    {
        $config = Yaf_Application::app ()->getConfig ();
        $config_arr = $config->toArray();
        if(empty($config_arr))
        {
            $err_msg = '['. date("Y-m-d H:i:s") . '], ' . "[Request IP is:{$_SERVER["REMOTE_ADDR"]}" .'], [error_detail: can\'t find any config file!]'. "\n";
            error_log($err_msg, 3, self::CANT_GET_CONFIG_FILE_LOG_PATH);
        }
        elseif(!isset($config_arr[$name]))
        {
            $err_msg = '['. date("Y-m-d H:i:s") . '], ' . "[Request IP is:{$_SERVER["REMOTE_ADDR"]}" .'], [error_detail: can\'t get '. "'{$name}' " .'info from config file!]' . "\n";
            error_log($err_msg, 3, self::CANT_GET_CONFIG_FILE_LOG_PATH);
            
        }
        else
        {
            return $config_arr[$name];
        }
    }
}
