<?php
/**
 *
 * @author zhoukk
 * @version 
 */
require_once 'Zend/View/Interface.php';

/**
 * getError helper
 *
 * @uses viewHelper Zend_View_Helper
 */
class Zend_View_Helper_GetError
{
    private $errorArray;

    public function __construct ()
    {
        $errorConfig = new Zend_Config_Ini('../application/configs/error.ini');
        $this->errorArray = $errorConfig->Error->toArray();
    }
    
    public function getError ($errorid)
    {
        $errMsg = $this->errorArray[$errorid];
        if (null == $errMsg) {
            return $errorid;
        }
        return $errMsg;
    }
    
}
