<?php

/**
 * Comment
 *  
 * @author zhoukk
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class Comment extends Zend_Db_Table_Abstract
{

    /**
     * The default table name
     */
    protected $_name = 'ps_comment';

}
