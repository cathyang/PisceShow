<?php

/**
 * CommentController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once '../application/models/Comment.php';
require_once '../application/models/Blog.php';

class CommentController extends Zend_Controller_Action
{

    public function init ()
    {
        
    }
    
    /**
     * The default action - show the home page
     */
    public function indexAction ()
    {
        // TODO Auto-generated CommentController::indexAction() default action
        $this->_helper->viewRenderer->setNoRender();
    }

    //显示评论列表
    public function showAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $sessdata = $storage->read();
        if (! $sessdata)
        {
            // 没登录 ， 转到登录页
            $this->_redirect('index/index');
        }
        $this->_helper->viewRenderer->setNoRender();
        
    }
    
    //发表评论
    public function publishAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $sessdata = $storage->read();
        if (! $sessdata)
        {
            // 没登录 ， 转到登录页
            $this->_redirect('index/index');
        }
        $comment = new Comment();
        $data = array();
        $data[comment_author] = $sessdata->login_id;
        $data[comment_blog] = $_POST[comment_blog];
        $data[comment_text] = $_POST[comment_text];
        $data[comment_parent] = $_POST[comment_parent];
        $data[comment_publish] = time();
        $comment->insert($data);
        
        $this->_redirect('user/home');
    }

    public function deleteAction ()
    {
    
    }
}
