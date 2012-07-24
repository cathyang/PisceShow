<?php

require_once '../application/models/Login.php';
require_once '../application/models/User.php';

class IndexController extends Zend_Controller_Action
{

    private $transport;

    private $arradmin;

    public function init ()
    {
        /*
         * Initialize action controller here
         */
        $ObConfig = new Zend_Config_Ini('../application/configs/application.ini');
        // 系统发邮件的配置
        $arrserver = $ObConfig->mail_server->toArray();
        $this->arradmin = $ObConfig->mail_admin->toArray();
        
        $this->transport = new Zend_Mail_Transport_Smtp($this->arradmin[smtp], $arrserver);
    }
    
    // 注册
    public function registAction ()
    {
        Zend_Loader::loadClass('Login');
        $login = new Login();
        $user = new User();
        if ($this->getRequest()->isPost())
        {
            $data = $_POST;
            // var_dump ( $data );
            if (! $data)
            {
                return;
            }
            if (! $data[login_name] || ! $data[login_pass])
            {
                return;
            }
            $this->view->data = array();
            if ('agree' != $data[agree])
            {
                return;
            }
            if ($data[login_pass] != $data[relogin_pass])
            {
                $this->view->data[error] = ERROR_REPASSWD;
                return;
            }
            if ($login->checkUnique($data[login_name]))
            {
                $this->view->data[error] = ERROR_NAME_EXIST;
                return;
            }
            unset($data[agree]);
            unset($data[relogin_pass]);
            // 生成md5加密的随机盐 长度为20的随机字符串
            $data[login_salt] = substr(md5(mt_rand()), 0, 20);
            // 对密码做md5加盐加密
            $data[login_pass] = md5($data[login_salt] . $data[login_pass]);
            $data[login_online] = 1;
            $data[login_last_ip] = $_SERVER[REMOTE_ADDR];
            $data[login_last_time] = time();
            $data[login_count] = 1;
            // 帐号冻结状态
            $data[login_frozen] = 0;
            
            var_dump ( $data );
            
            // 插入新的数据到帐号登录表
            $login_id = $login->insert($data);
            
            $username = $data[login_name];
            $arr = split('@', $username);
            
            // 读取用户默认头像的二进制数据 保存到数据库
            $filename = './img/default_photo.jpg';
            $photodata = fread(fopen($filename, 'r'), filesize($filename));
            
            // 生成一个新的用户
            $user = new User();
            $data = array();
            $data[user_id] = $login_id;
            $data[user_nick] = $arr[0];
            $data[user_blog_name] = $arr[0];
            $data[user_photo_type] = 'image/jpeg';
            $data[user_photo] = $photodata;
            $data[user_domain] = $arr[0] . '.pisceshow.com';
            
            // 增加新的用户数据
            $user->insert($data);
            
            // 注册完后 直接转到个人主页
            $this->_redirect('user/home');

        }
    }
    
    // 检查重复用户名
    public function checkuserAction ()
    {
        // 检查的id
        $id = $this->_request->get("id");
        if (null == $id)
        {
            return;
        }
        // echo "checkuser:" . $id . "\n\n";
        $this->_helper->viewRenderer->setNoRender();
        $login = new Login();
        if ($login->checkUnique($id))
        {
            // echo $this->view->getError(ERROR_NAME_EXIST);
            echo ERROR_NAME_EXIST;
            // $this->view->data[error] = ERROR_NAME_EXIST;
        }
    }
    
    // 登录
    public function indexAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();
        if ($data)
        {
            // 已经登录了 转到个人主页
            $this->_redirect('user/home');
            return;
        }
        if ($this->getRequest()->isPost())
        {
            // 登录请求
            $login = new Login();
            $data = $_POST;
            // var_dump($data);
            if (! $data || ! $data[code] || ! $data[login_name] || ! $data[login_pass])
            {
                return;
            }
            
            $this->view->data = array();
            
            // 判断验证码
            $captchaSession = new Zend_Session_Namespace("word");
            $code = $data[code];
            if ($code != $captchaSession->word)
            {
                $this->view->data[error] = ERROR_CHECK_CODE;
                return;
            }
            
            // 登录帐号密码 验证
            $auth = Zend_Auth::getInstance();
            $authAdapter = new Zend_Auth_Adapter_DbTable($login->getAdapter(), 'ps_login', 'login_name', 'login_pass', 'MD5(CONCAT(login_salt,?))');
            $authAdapter->setIdentity($data[login_name])->setCredential($data[login_pass]);
            $result = $auth->authenticate($authAdapter);
            if ($result->isValid())
            {
                // 验证成功 把登录结果写入session
                $storage = new Zend_Auth_Storage_Session();
                $row = $authAdapter->getResultRowObject();
                $storage->write($row);
                
                // 登录成功后 更新登录表信息
                $login = new Login();
                $curlogin[login_online] = 1;
                $curlogin[login_last_ip] = $_SERVER[REMOTE_ADDR];
                $curlogin[login_last_time] = time();
                $curlogin[login_count] = $row->login_count + 1;
                $login->update($curlogin, array('login_id = ?' => $data[login_id]));
                
                // 登录成功 转到个人主页
                $this->_redirect('user/home');
            }
            else
            {
                $this->view->data[error] = ERROR_PASSWORD;
            }
        }
    }
    
    // 登出
    public function logoutAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();
        if ($data)
        {
            // 已经登录的 做登出
            $login = new Login();
            
            // 标志下线 登出 并更新到数据库
            $curlogin[login_online] = 0;
            $login->update($curlogin, array('login_id = ?' => $data->login_id));
            
            // 清除session
            $storage->clear();
        }
        // 登出后 转到登录页
        $this->_redirect('index/index');
    }
    
    // 更改密码
    public function changepwAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();
        if (! $data)
        {
            // 没登录 不能改密码， 转到登录页
            $this->_redirect('index/index');
        }
        if ($this->getRequest()->isPost())
        {
            $oldpw = $_POST[old_passwd];
            $newpw = $_POST[new_passwd];
            if ($newpw != $_POST[renew_passwd])
            {
                // 2次密码不一样， 客户端优先过滤，后台验证
                return;
            }
            
            $login_id = $data->login_id;
            $login = new Login();
            $result = $login->find($login_id);
            if (! $result)
            {
                // 没这个用户，改毛密码，坑人
                return;
            }
            
            // 验证旧密码是否真确
            $result = $result->toArray();
            $login_salt = $result[0][login_salt];
            if ($result[0][login_pass] != MD5($login_salt . $oldpw))
            {
                return;
            }
            
            // 更改密码 并更新至数据库
            $data = array();
            $data[login_salt] = substr(md5(mt_rand()), 0, 20);
            $data[login_pass] = md5($data[login_salt] . $newpw);
            $login->update($data, array('login_id = ?' => $login_id));
            
            // 改完密码了 转到个人主页
            $this->_redirect('user/home');
        }
    }

    public function forgotpwAction ()
    {
    
    }

    private function getMailBody ($id)
    {
        $body = "click link blow:<br/>";
        $body = $body . "<a href='http://localhost/index/resetpw/id/" . $id;
        $body = $body . "' target='_blank'>http://localhost/index/resetpw/id/" . $id;
        $body = $body . "</a>";
        return $body;
    }
    
    // 忘记密码 发送重置密码邮件
    public function forgetpwmailAction ()
    {
        if ($this->getRequest()->isPost())
        {
            $data = $_POST;
            // var_dump($data);
            if (! $data || ! $data[code] || ! $data[login_name])
            {
                return;
            }
            // 验证码判断
            $captchaSession = new Zend_Session_Namespace("word");
            $code = $data[code];
            
            $this->view->data = array();
            
            if ($code != $captchaSession->word)
            {
                $this->view->data[error] = ERROR_CHECK_CODE;
                return;
            }
            
            // 判断是否有此用户
            $login = new Login();
            if (! $login->checkUnique($data[login_name]))
            {
                $this->view->data[error] = ERROR_USER_NOT_EXIST;
                return;
            }
            
            // 发送重置密码连接的邮件到用户名邮件地址
            $mail = new Zend_Mail('UTF-8');
            $mail->setDefaultTransport($this->transport);
            
            // 生成重置连接的随机字符串 (长度8)
            $randstr = substr(md5(mt_rand()), 0, 8);
            
            // 转成大写
            $randstr = strtoupper($randstr);
            
            // 邮件内容
            $body = $this->getMailBody($randstr . base64_encode($data[login_name]));
            
            // 设置邮件
            $mail->setBodyHtml($body);
            $mail->setFrom($this->arradmin[frommail], $this->arradmin[fromnick]);
            $mail->addTo($data[login_name], $data[login_name]);
            $mail->setSubject($this->arradmin[subject]);
            
            // 发送邮件
            try
            {
                $mail->send();
                $this->view->data[mail] = $data[login_name];
                
                // 发送成功 设置邮件内连接的有效时间 （有效时间24小时）
                $expiretime = time() + 24 * 60 * 60;
                $login = new Login();
                $curlogin[login_resetpw_time] = $expiretime;
                $curlogin[login_resetpw_rand] = $randstr;
                $login->update($curlogin, array('login_name = ?' => $data[login_name]));
            
            }
            catch (Zend_Mail_Exception $e)
            {
                $this->view->data[error] = $e->getMessage();
            }
        
        }
    }
    
    // 邮件中请求重置密码
    public function resetpwAction ()
    {
        // 连接中带的用户名参数
        $id = $this->_request->get("id");
        $randstr = substr($id, 0, 8);
        
        $login_id = base64_decode(substr($id, 8, strlen($id) - 8));
        
        $this->view->data = array();
        if ($this->getRequest()->isPost())
        {
            $data = $_POST;
            // var_dump ( $data );
            if (! $data || ! $data[new_passwd] || ! $data[renew_passwd])
            {
                return;
            }
            // 2次密码不一样，应该在客户端做判断过滤此错误，后台验证
            if ($data[new_passwd] != $data[renew_passwd])
            {
                $this->view->data[error] = ERROR_REPASSWD;
                return;
            }
            // 看看有没有这个用户了
            $login = new Login();
            $result = $login->find($login_id);
            if (! $result)
            {
                echo '1';
                $this->view->data[error] = ERROR_RESET_PASS_LINK;
                return;
            }
            
            // 判断连接是否还有效 连接是否处理过 或过了有效时间
            $result = $result->toArray();
            if (count($result) <= 0)
            {
                echo '2';
                $this->view->data[error] = ERROR_RESET_PASS_LINK;
                return;
            }
            
            if ($randstr != $result[0][login_resetpw_rand])
            {
                // 随机字符串不匹配，错误的连接
                echo '3';
                $this->view->data[error] = ERROR_RESET_PASS_LINK;
                return;
            }
            
            $expiretime = $result[0][login_resetpw_time];
            if ($expiretime < time() || 0 == $expiretime)
            {
                // 连接过期了
                $this->view->data[error] = ERROR_RESET_LINK_EXPIRE;
                return;
            }
            
            // 设置新的密码 清零重置过期时间 更新到数据库
            $login_salt = $result[0][login_salt];
            $arr = array();
            $arr[login_resetpw_time] = 0;
            $arr[login_salt] = substr(md5(mt_rand()), 0, 20);
            $arr[login_pass] = md5($arr[login_salt] . $data[new_passwd]);
            $login->update($arr, array('login_id = ?' => $login_id));
            
            // 设置后 转到登录页
            $this->_redirect('index/index');
        }
        else
        {
            $this->view->data[user] = $login_id;
            $this->view->data[rand] = $randstr;
        }
    }

    public function helloAction ()
    {
        $this->_helper->viewRenderer->setNoRender();
    }

}

