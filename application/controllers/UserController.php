<?php

/**
 * UserController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once '../application/models/User.php';
require_once '../application/models/Relationship.php';
require_once '../application/models/Blog.php';

class UserController extends Zend_Controller_Action
{

    /**
     * The default action - show the home page
     */
    
    public function init ()
    {
    
    }

    public function indexAction ()
    {
        // TODO Auto-generated UserController::indexAction() default action
        $this->_helper->viewRenderer->setNoRender();
    }
    
    // 涓汉涓婚〉
    public function homeAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();
        if (! $data)
        {
            // 杩樻病鐧诲綍 杞埌鐧诲綍椤�
            $this->_redirect('index/index');
        }
        $this->view->data = array();
        
        // 鐧诲綍浜�鍒楀嚭鍏虫敞鑰�鍜�绮変笣鍒楄〃
        $user = new User();
        $userdata = $user->find($data->login_id)->toArray();
        $this->view->data[user] = $userdata[0];
        $relationship = new Relationship();
        
        // 鍒楄〃鏁版嵁浼犲埌view 鍋氭樉绀�
        $this->view->data[followers] = $relationship->getFollows($data->login_id);
        $this->view->data[befollowers] = $relationship->getBeFollows($data->login_id);
        
        $blog = new Blog();
        $totalnum = (int) $blog->getAllBlogsTotal($data->login_id);
        $page = (int) $this->getRequest()->getParam('page');
        if (0 == $page)
        {
            $page = 1;
        }
        $numPerPage = 8; // 姣忛〉鏄剧ず鐨勬潯鏁�
        $page_total = ceil($totalnum / $numPerPage);
        $page = ($page > $page_total) ? $page_total : $page;
        
        $paginator = Zend_Paginator::factory($totalnum);
        $paginator->setCurrentPageNumber($page)
            ->setPageRange(5)
            ->setItemCountPerPage($numPerPage);
        $this->view->paginator = $paginator;
        $offset = ($page - 1) * $numPerPage;
        $this->view->data[blogs] = $blog->getAllBlogs($data->login_id, $numPerPage, $offset);
        $response = $this->getResponse();
        $response->insert('sidebar', $this->view->render('sidebar.phtml'));
        $response->insert('followers', $this->view->render('followers.phtml'));
        $response->insert('newblog', $this->view->render('newblog.phtml'));
    }
    
    // 鏌ユ壘鎵�湁娉ㄥ唽鐢ㄦ埛
    public function searchAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();
        if (! $data)
        {
            // 2涓烦杞〉闈㈡晥鏋滀笉涓�牱锛屼竴涓湴鍧�彉 涓�釜鍦板潃涓嶅彉
            $this->_redirect('index/index');
            // $this->_forward('index', 'index');
        }
        $user = new User();
        $this->view->data = array();
        $this->view->data[users] = $user->allUsers();
    }
    
    // 鍏虫敞
    public function followAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();
        if (! $data)
        {
            $this->_redirect('index/index');
        }
        $self = $data->login_id;
        $id = $this->_request->get("id");
        $this->view->data = array();
        
        if ($id != $self)
        {
            $relationship = new Relationship();
            if (true == $relationship->follow($self, $id))
            {
                $user = new User();
                $user->incFollowersOrBefollowers($self, 'user_followers');
                $user->incFollowersOrBefollowers($id, 'user_befollowers');
            }
        }
        else
        {
            $this->view->data[error] = 'can not follow yourself';
        }
        $this->_redirect('user/home');
    }
    
    // 鍙栨秷鍏虫敞
    public function unfollowAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();
        if (! $data)
        {
            $this->_redirect('index/index');
        }
        $self = $data->login_id;
        $id = $this->_request->get("id");
        if ($id != $self)
        {
            $relationship = new Relationship();
            $this->view->data = array();
            if (true == $relationship->unfollow($self, $id))
            {
                $user = new User();
                $user->decFollowersOrBefollowers($self, 'user_followers');
                $user->decFollowersOrBefollowers($id, 'user_befollowers');
            }
        }
        $this->_redirect('user/home');
    }
    
    // 涓婁紶澶村儚
    public function uploadAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();
        if (! $data)
        {
            // 娌＄櫥褰曠殑 涓嶈鏀瑰ご鍍�
            $this->_redirect('index/index');
        }
        $this->_helper->viewRenderer->setNoRender();
        if ($this->getRequest()->isPost())
        {
            // 楠岃瘉涓婁紶鐨勫ご鍍忔槸鍚︽湁鏁堢殑
            $upload = new Zend_File_Transfer_Adapter_Http();
            // $upload->setDestination('./img');
            $upload->addValidator('MimeType', false, array('image/png', 'image/jpeg', 'image/jpg'));
            $upload->addValidator('ImageSize', false, array('minwidth' => 10, 'maxwidth' => 1024, 'minheight' => 10, 'maxheight' => 768));
            if ($upload->isValid())
            {
                
                // 楠岃瘉鎴愬姛浜嗭紝 鏇存柊鏁版嵁搴撲腑鐢ㄦ埛澶村儚鏁版嵁
                $upload->receive();
                $files = $upload->getFileInfo();
                $photodata = fread(fopen($files[head_photo]['tmp_name'], 'r'), $files[head_photo]['size']);
                $user = new User();
                $login_id = $data->login_id;
                $data = array();
                $data['user_photo'] = $photodata;
                $data['user_photo_type'] = $files[head_photo]['type'];
                $user->update($data, array('user_id = ?' => $login_id));
                
                // 澶村儚鏀瑰畬鍚庡埌 涓汉涓婚〉
                $this->_redirect('user/home');
            }
            else
            {
                print_r($upload->getMessages());
            }
        }
    
    }
    
    // 鏄剧ず澶村儚璇锋眰
    public function showheadAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout()->disableLayout();
        // 璇锋眰鏄剧ず澶村儚鐨勭敤鎴穒d
        $user_id = $this->_request->get("id");
        
        // 鏁版嵁搴撲腑load鐢ㄦ埛澶村儚鐨勪簩杩涘埗鏁版嵁
        $user = new User();
        $userdata = $user->find($user_id)->toArray();
        if ($userdata[0][user_photo])
        {
            // 鎵惧埌浜�鎸夊浘鐗囨牸寮忔樉绀�
            $type = $userdata[0][user_photo_type];
            header("Content-type:" . $type);
            echo $userdata[0][user_photo];
        }
        else
        {
            // 鎵句笉鍒颁簡 灏辩粰涓粯璁ゅご鍍忓惂
            echo './img/default_photo.jpg';
        }
    }
}
