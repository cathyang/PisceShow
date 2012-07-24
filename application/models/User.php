<?php

/**
 * User
 *  
 * @author Kevin
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class User extends Zend_Db_Table_Abstract
{

    /**
     * The default table name
     */
    protected $_name = 'ps_user';
    
    public function allUsers() {
        $select = $this->fetchAll();
        return $select->toArray();
    }
    
    public function incFollowersOrBefollowers($self,$prop)
    {
    	$select = $this->select()
    	->from($this->_name)
    	->where('user_id = ?', $self);
    	$result = $this->fetchRow($select);
    	if ($result)
    	{
    		$result[$prop] = $result[$prop] + 1;
    		$result->save();
    	}
    }
    
    public function decFollowersOrBefollowers($self,$prop)
    {
    	$select = $this->select()
    	->from($this->_name)
    	->where('user_id = ?', $self);
    	$result = $this->fetchRow($select);
    	if ($result)
    	{
    		$result[$prop] = $result[$prop] - 1;
    		$result->save();
    	}
    }
    
    public function incBlogs($user_id){
        $select = $this->select()
        ->from($this->_name)
        ->where('user_id = ?', $user_id);
        $result = $this->fetchRow($select);
        if ($result)
        {
            $result['user_blogs'] = $result['user_blogs'] + 1;
            $result->save();
        }
    }
}
