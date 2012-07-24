<?php

/**
 * Relationship
 *  
 * @author Kevin
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class Relationship extends Zend_Db_Table_Abstract
{

    /**
     * The default table name
     */
    protected $_name = 'ps_relationship';

    public function follow ($self, $target)
    {
        if ($self == $target)
        {
            return false;
        }
        $select = $this->select()
            ->from($this->_name)
            ->where('relationship_user = ?', $self)
            ->where('relationship_follower = ?', $target);
        $result = $this->fetchRow($select);
        if ($result)
        {
            if (2 == $result[relationship_mode])
            {
                $result[relationship_mode] = 3;
                $result->save();
                return true;
            }
            return false;
        }
        $select = $this->select()
            ->from($this->_name)
            ->where('relationship_user=?', $target)
            ->where('relationship_follower=?', $self);
        $result = $this->fetchRow($select);
        if ($result)
        {
            if (1 == $result[relationship_mode])
            {
                $result[relationship_mode] = 3;
                $result->save();
                return true;
            }
            return false;
        }
        $data = array('relationship_user' => $self, 
                'relationship_follower' => $target, 'relationship_mode' => 1);
        $this->insert($data);
        return true;
    }

    public function unfollow ($self, $target)
    {
        
        if ($self == $target)
        {
            return false;
        }
        $select = $this->select()
            ->from($this->_name)
            ->where('relationship_user = ?', $self)
            ->where('relationship_follower = ?', $target);
        $result = $this->fetchRow($select);
        if ($result)
        {
            if (3 == $result[relationship_mode])
            {
                $result[relationship_mode] = 2;
                $result->save();
                return true;
            }
            else 
                if (1 == $result[relationship_mode])
                {
                    $result->delete();
                    return true;
                }
            return false;
        }
        $select = $this->select()
            ->from($this->_name)
            ->where('relationship_user=?', $target)
            ->where('relationship_follower=?', $self);
        $result = $this->fetchRow($select);
        if ($result)
        {
            if (3 == $result[relationship_mode])
            {
                $result[relationship_mode] = 1;
                $result->save();
                return true;
            }
            else 
                if (2 == $result[relationship_mode])
                {
                    $result->delete();
                    return true;
                }
            return false;
        }
        return false;
    }

    public function getFollows ($self)
    {
        $select = $this->select()
            ->from($this->_name)
            ->where('relationship_user=? AND relationship_mode<>2', $self)
            ->orWhere('relationship_follower=? AND relationship_mode<>1', $self);
        if (!$select) {
            return null;
        }
        $result = $this->fetchAll($select);
        if (!$result)
        {
            return null;
        }
        $result = $result->toArray();
        $data = array();
        foreach ($result as $key => $val)
        {
            if ($val[relationship_user] == $self)
            {
                $data[] = $val[relationship_follower];
            }
            else 
                if ($val[relationship_follower] == $self)
                {
                    $data[] = $val[relationship_user];
                }
        }
        return $data;
    }

    public function getBeFollows ($self)
    {
        $select = $this->select()
            ->from($this->_name)
            ->where('relationship_user=?', $self)
            ->where('relationship_mode<>?', 1)
            ->orWhere('relationship_follower=?', $self)
            ->where('relationship_mode<>?', 2);
        $result = $this->fetchAll($select)->toArray();
        $data = array();
        foreach ($result as $key => $val)
        {
            if ($val[relationship_user] == $self)
            {
                $data[] = $val[relationship_follower];
            }
            else 
                if ($val[relationship_follower] == $self)
                {
                    $data[] = $val[relationship_user];
                }
        }
        return $data;
    }
}
