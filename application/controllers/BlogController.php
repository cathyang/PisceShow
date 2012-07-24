<?php

/**
 * BlogController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once '../application/models/Blog.php';
require_once '../application/models/User.php';

class BlogController extends Zend_Controller_Action
{

    /**
     * The default action - show the home page
     */
    
    public function init ()
    {
        
    }

    public function indexAction ()
    {
        // TODO Auto-generated BlogController::indexAction() default action
    }

    public function publishAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $sessdata = $storage->read();
        if (! $sessdata)
        {
            // 没登录 不能改密码， 转到登录页
            $this->_redirect('index/index');
        }
        if ($this->getRequest()->isPost())
        {
            $blog = new Blog();
            $data = array();
            $data[blog_title] = $_POST[blog_title];
            $data[blog_text] = $_POST[blog_text];
            $data[blog_author] = $sessdata->login_id;
            $data[blog_publish] = time();
            $data[blog_modify] = time();
            $blog->insert($data);
            
            $user = new User();
            $user->incBlogs($sessdata->login_id);
            // var_dump($data);
        }
        $this->_redirect('user/home');
    }

    public function deleteAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $sessdata = $storage->read();
        if (! $sessdata)
        {
            // 没登录 ， 转到登录页
            $this->_redirect('index/index');
        }
        $blog_id = $this->_request->get("id");
        $blog = new Blog();
        $blog->delBlog($sessdata->login_id, $blog_id);
        $this->_redirect('user/home');
    }

    public function updateAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $sessdata = $storage->read();
        if (! $sessdata)
        {
            // 没登录 ， 转到登录页
            $this->_redirect('index/index');
        }
        $this->_helper->viewRenderer->setNoRender();
        if ($this->getRequest()->isPost()) {
            $blog_id = $_POST['blog_id'];
            $blog = new Blog();
            $blog->updateBlog($sessdata->login_id, $blog_id, $_POST);
            
            $this->_redirect('user/home');
        }
        else
        {
            $blog_id = $this->_request->get("id");
            $blog = new Blog();
            $this->view->data = array();
            $this->view->data[blog] = $blog->getBlog($sessdata->login_id, $blog_id);
            if (null == $this->view->data[blog]) {
                return;
            }
            $response = $this->getResponse();
            $response->insert('newblog', $this->view->render('newblog.phtml'));
        }
    }
}
