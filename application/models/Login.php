<?php

/**
 * Users
 *  
 * @author zhoukk
 * @version 
 */

class Login extends Zend_Db_Table_Abstract
{

    /**
     * The default table name
     */
    protected $_name = 'ps_login';

    function checkUnique ($username)
    {
        $select = $this->_db->select()
            ->from($this->_name, array('login_name'))
            ->where('login_name=?', $username);
        $result = $this->getAdapter()->fetchOne($select);
        if ($result)
        {
            return true;
        }
        return false;
    }

    function allUsers ()
    {
        $select = $this->fetchAll();
        return $select->toArray();
    }
}
