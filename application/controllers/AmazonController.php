<?php


class AmazonController extends Zend_Controller_Action
{
	private $amazon;
	
	public function init()
	{
		$helper = $this->_helper->getHelper('Layout');
		$this->layout =$helper->getLayoutInstance();
		
		$this->_helper->viewRenderer->setNoRender();
		$this->layout->disableLayout();
		
		header('Content-type: application/json');
		
		$this->amazon = new Application_Model_Provider_Amazon();
	}
	
	public function indexAction()
	{
		$this->amazon->create_user_key_pair();
	}
	
	public function launchInstance()
	{
		// $cassie->column_families['USER_SERVERS']->INSERT();
		$params = array(
			''
		);
		$this->amazon->launch_server($params);
	}

}