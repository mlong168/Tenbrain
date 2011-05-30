<?php


class RackspaceController extends Zend_Controller_Action
{
	private $rack;
	
	public function init()
	{
		$helper = $this->_helper->getHelper('Layout');
		$this->layout =$helper->getLayoutInstance();
		
		$this->_helper->viewRenderer->setNoRender();
		$this->layout->disableLayout();
		
		header('Content-type: application/json');
		
		$this->rack = new Application_Model_Provider_Rackspace();
	}
	
	public function indexAction()
	{
		// $this->rack->create_user_key_pair();
	}
	
	public function launchInstanceAction()
	{
		$params = array(
			'image_id'	=> $state = $this->getRequest()->getParam('image_id'),
			'name'		=> $state = $this->getRequest()->getParam('name'),
			'flavor_id'	=> $state = $this->getRequest()->getParam('flavor_id')
		);
		echo Zend_Json_Encoder::encode(array(
			'success'	=> $this->rack->launch_server($params)
		));
	}
	
	public function getFlavorsAction()
	{
		echo Zend_Json::encode(array(
			'success'	=> true,
			'flavors'	=> $this->rack->list_flavors()
		));
	}

}