<?php

/**
 * CommonController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

class CommonController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		// TODO Auto-generated CommonController::indexAction() default action
	    $this->_helper->viewRenderer->setNoRender();
	}
	
	// 显示验证码请求
	public function showAction() {
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout()->disableLayout();
		$captchaSession = new Zend_Session_Namespace ( "word" );
		$captcha = new Zend_Captcha_Image ( array ('font' => './font/pisceshow.ttf', 		// 验证码字体文件
		'fontsize' => 24, 		// 验证码字体大小
		'imgdir' => './captcha', 		// 验证码生成路径
		'session' => $captchaSession, 'width' => 120, 		// 验证码图片宽度
		'height' => 40, 		// 验证码图片高度
		'wordlen' => 4, 		// 验证码字数
		'DotNoiseLevel' => 5 ) ); // 噪点度
		
		$captcha->setExpiration ( 5 );
		$captcha->setGcFreq ( 3 );
		$captcha->generate ();
		$captchaSession->word = $captcha->getWord ();
		
		// 验证码图片文件名 路径 + id + 后缀名
		$filename = $captcha->getImgDir () . $captcha->getId () . $captcha->getSuffix ();
		$data = fread ( fopen ( $filename, 'r' ), filesize ( $filename ) );
		header ( "Content-type:image/png" );
		echo $data;
	}
}
