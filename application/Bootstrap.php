<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    protected function _initDB()
    {
        $ObConfig = new Zend_Config_Ini('../application/configs/application.ini');
        
        $db = Zend_Db::factory($ObConfig->database);
        Zend_Db_Table::setDefaultAdapter($db);
    }
}

