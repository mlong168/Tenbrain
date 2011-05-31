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
		// $this->amazon->create_user_key_pair();
	}
	
	public function launchInstanceAction()
	{
		$params = array(
			'image_id'	=> $state = $this->getRequest()->getParam('image_id'),
			'name'		=> $state = $this->getRequest()->getParam('instance_name'),
			'type'		=> $state = $this->getRequest()->getParam('instance_type')
		);
		echo Zend_Json_Encoder::encode(array(
			'success'	=> $this->amazon->launch_server($params)
		));
	}
	
	public function getAvailableInstanceTypesAction()
	{
		echo Zend_Json_Encoder::encode(array(
			'success'	=> true,
			'types'		=> $this->amazon->get_available_server_types()
		));
	}
	
	public function downloadPrivateKeyAction()
	{
		$auth = Zend_Auth::getInstance();
		$user_id = $auth->getIdentity()->id;
		
		$key = $this->amazon->get_user_private_key($user_id);
		
		header('Content-type: text/plain');
		header('Content-Disposition: attachment; filename="' . $key['name'] . '.pem"');
		echo $key['key'];
		return false;
	}

}