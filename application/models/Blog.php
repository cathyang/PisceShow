<?php

/**
 * Blog
 *  
 * @author zhoukk
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';
require_once 'Relationship.php';

class Blog extends Zend_Db_Table_Abstract
{

    /**
     * The default table name
     */
    protected $_name = 'ps_blog';
    
    // 获取自己所有博客列表
    public function getUserBlogs ($user_id, $numPerPage, $offset)
    {
        try
        {
            $select = $this->_db->select()
                ->from($this->_name)
                ->where('blog_author=?', $user_id)
                ->where('blog_del=?', '0')
                ->order('blog_id DESC')
                ->limit($numPerPage, $offset);
            $sql = $select->__toString();
            $array = $this->getAdapter()
                ->query($sql)
                ->fetchAll();
            return $array;
        }
        catch (Exception $e)
        {
            return false;
        }
    }
    
    // 获取自己和关注者所有的博客列表
    public function getAllBlogs ($user_id, $numPerPage, $offset)
    {
        $relationship = new Relationship();
        $users = $relationship->getFollows($user_id);
        array_push($users, $user_id);
        try
        {
            $select = $this->_db->select()
                ->from(array('a' => $this->_name))
                ->joinLeft(array('b' => 'ps_user'), 'a.blog_author=b.user_id')
                ->where('a.blog_author in (' . implode(',', $users) . ')')
                ->where('a.blog_del=?', '0')
                ->order('a.blog_id DESC')
                ->limit($numPerPage, $offset);
            $sql = $select->__toString();
            $array = $this->getAdapter()
                ->query($sql)
                ->fetchAll();
            return $array;
        }
        catch (Exception $e)
        {
            return false;
        }
    }
    
    // 获取自己所有博客数
    public function getUserBlogsTotal ($user_id)
    {
        try
        {
            $select = $this->_db->select()
                ->from($this->_name, 'count(blog_id) as total')
                ->where('blog_author=?', $user_id)
                ->where('blog_del=?', '0');
            $sql = $select->__toString();
            $nums = $this->getAdapter()
                ->query($sql)
                ->fetchColumn();
            return $nums;
        }
        catch (Exception $e)
        {
            return false;
        }
    }
    
    // 获取自己和关注者所有的博客数
    public function getAllBlogsTotal ($user_id)
    {
        $relationship = new Relationship();
        $users = $relationship->getFollows($user_id);
        array_push($users, $user_id);
        try
        {
            $select = $this->_db->select()
                ->from($this->_name, 'count(blog_id) as total')
                ->where('blog_author in (' . implode(',', $users) . ')')
                ->where('blog_del=?', '0');
            $sql = $select->__toString();
            // echo 'sql:' . $sql . '<br/>';
            $nums = $this->getAdapter()
                ->query($sql)
                ->fetchColumn();
            return $nums;
        }
        catch (Exception $e)
        {
            return false;
        }
    }
    
    // 删除博客
    public function delBlog ($user_id, $blog_id)
    {
        $select = $this->select()
            ->from($this->_name)
            ->where('blog_id = ?', $blog_id)
            ->where('blog_author = ?', $user_id)
            ->where('blog_del = ?', '0');
        $result = $this->fetchRow($select);
        if ($result)
        {
            $result['blog_del'] = '1';
            $result->save();
        }
    }
    
    public function getBlog($user_id, $blog_id)
    {
        $select = $this->select()
        ->from($this->_name)
        ->where('blog_id = ?', $blog_id)
        ->where('blog_author = ?', $user_id)
        ->where('blog_del = ?', '0');
        $result = $this->fetchRow($select);
        if ($result)
        {
            return $result->toArray();
        }
    }
    
    public function updateBlog($user_id, $blog_id, $data)
    {
        $select = $this->select()
        ->from($this->_name)
        ->where('blog_id = ?', $blog_id)
        ->where('blog_author = ?', $user_id)
        ->where('blog_del = ?', '0');
        $result = $this->fetchRow($select);
        if ($result)
        {
            $result[blog_title] = $data[blog_title];
            $result[blog_text] = $data[blog_text];
            $result[blog_modify] = time();
            $result->save();
        }
    }
}
