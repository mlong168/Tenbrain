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
	
	public function setUserApiCredentialsAction()
	{
		$request = $this->getRequest();
		$key = $request->getParam('key');
		$secret_key = $request->getParam('secret_key');
		
		$amazon_model = new Application_Model_Provider_Amazon();
		$amazon_model->set_user_aws_credentials($key, $secret_key);
		
		echo Zend_Json::encode(array('success' => true));
		return true;
	}
	
	public function getUserApiCredentialsAction()
	{
		$amazon_model = new Application_Model_Provider_Amazon();
		$credentials = $amazon_model->get_user_aws_credentials();
		if($credentials)
		{
			echo Zend_Json::encode(array(
				'success'	=> true,
				'key'		=> $credentials['key'],
				'secret_key'=> $credentials['secret_key']
			));
		}
		else
		{
			echo Zend_Json::encode(array('success' => false));
		}
		
		return true;
	}

	function elasticIpsAction()
	{
		echo Zend_Json::encode(array(
			'success'		=> true,
			'elastic_ips'	=> $this->amazon->get_elastic_ips()
		));
	}

	function allocateAddressAction()
	{
		$address = $this->amazon->allocate_address();
		echo Zend_Json::encode(array(
			'success' => (bool) $address,
			'address' => $address
		));
	}

	function getShortInstancesListAction()	// for associating with an elastic IP
	{
		echo Zend_Json::encode(array(
			'success'	=> true,
			'instances' => $this->amazon->get_short_instances_list()
		));
	}

	function associateElasticIpAction()
	{
		$request = $this->getRequest();
		echo Zend_Json::encode(array(
			'success'	=> $this->amazon->associate_ip(
				$request->getParam('instance_id'),
				$request->getParam('address')
			)
		));
	}

	function disassociateAddressAction()
	{
		$request = $this->getRequest();
		echo Zend_Json::encode(array(
			'success'	=> $this->amazon->disassociate_ip($request->getParam('address'))
		));
	}

	function releaseAddressesAction()
	{
		$request = $this->getRequest();
		$ips = Zend_Json::decode($request->getParam('addresses'));

		echo Zend_Json::encode(array(
			'success'	=> $this->amazon->release_ip($ips)
		));
	}

}