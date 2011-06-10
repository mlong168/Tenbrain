<?php
class ActionAllower
{
	private $user, $providers;
	private $_acl = array();
	private $supported_providers = array('Amazon', 'Rackspace', 'GoGrid');
	
    function __construct ()
    {
    	$this->user = Zend_Auth::getInstance()->getIdentity();
    	
    	$this->_acl = new ZendExt_ACL($this->user);
    	
    	foreach($this->supported_providers as $provider_name)
		{
			$model_name = 'Application_Model_Provider_' . $provider_name;
			$this->providers[$provider_name] = new $model_name();
		}
    }
    
	private function failure_response($message, $additional_params = array())
	{
		echo Zend_Json::encode(array_merge(array(
			'success'	=> false,
			'message'	=> $message
		), $additional_params));
		
		die;
	}
    
    private function get_deployed_servers_count()
    {
    	$servers_model = new Application_Model_Servers();
    	
    	$i = 0;	$state = 'running';
    	
    	$servers = $servers_model->get_user_servers();

    	return count($servers);
    }
    
	private function get_backup_count()
    {
   		$i = 0;
		foreach($this->providers as $provider)
		{
			$bkps = $provider->created_backups();
			$i += count($bkps);
		}
		return $i;
    }
    
	private function get_lb_count()
    {
    	$balancer_model = new Application_Model_Balancer();
		$balancers = $balancer_model->get_user_load_balancers();
		
		return count($balancers);
    }
    
	private function get_micro_count()
    {
    	$state = 'running';
    	$servers = $this->providers['Amazon']->list_servers($provider_servers[$provider->name], $state);
		return count($servers);
    }
    
    public function isAllowedToDeployServer($bool_response = false)
    {
    	if($this->isAllowedAll())
    		return true;
    	
    	$count = $this->get_deployed_servers_count();
    	$isAllowed = $this->_acl->isUserAllowedCount("Server", "deploy", $count);

    	if(!$isAllowed)
    	{
    		if($bool_response)
    			return false;
    		$this->failure_response("This action is limited.");
    	}
    }
    
	public function isAllowedToCreateBackup($bool_response = false)
    {
    	if($this->isAllowedAll())
    		return true;
    	
    	$count = $this->get_backup_count();
    	$isAllowed = $this->_acl->isUserAllowedCount("Backups", "create", $count);
    	
   		if(!$isAllowed)
    	{
    		if($bool_response)
    			return false;
    		$this->failure_response("This action is limited.");
    	}
    }
    
	public function isAllowedToCreateElasticIP($bool_response = false)
    {
    	if($this->isAllowedAll())
    		return true;
    		
    	if($bool_response)
    			return false;	
    	$this->failure_response("This action is limited.");
    }
    
	public function isAllowedToCreateMicroInstance($bool_response = false)
    {
    	if($this->isAllowedAll())
    		return true;
   
    	$count = $this->get_micro_count();
    	$isAllowed = $this->_acl->isUserAllowedCount("Server", "deploy_micro", $count);
    	
   		if(!$isAllowed)
    	{
    		if($bool_response)
    			return false;
    		$this->failure_response("This action is limited.");
    	}
    }
    
	public function isAllowedAll()
    {
    	if($this->_acl->isUserAllowed('Action','All'))
    		return true;
    }
    
    public function isAllowedGoGridFunctionality($bool_response = false)
    {
    	$credentials = $this->get_user_credentials('GoGrid');
    	if($this->_acl->isUserAllowed('Action','GoGrid') && $credentials)
    		return true;
    		
    	if($bool_response)
    		return false;
    	$this->failure_response("This action is limited.");
    }
    
	public function isAllowedRackspaceFunctionality($bool_response = false)
    {
    	$credentials = $this->get_user_credentials('Rackspace');
    	if($this->_acl->isUserAllowed('Action','Rackspace') && $credentials)
    		return true;
    		
    	if($bool_response)
    		return false;
    	$this->failure_response("This action is limited.");
    }
    
    private function isUserAllowedAction($resource, $permission, $count)
    {
    	$this->_acl->isUserAllowedCount($resource, $permission, $count);
    }
    
    private function get_user_credentials($provider)
    {
    	$modelname = 'Application_Model_DbTable_Credentials_'.$provider;
    	$credentials_model = new $modelname();
    	$user_id = $this->user->id;
    	return $credentials_model->get_credentials($user_id);
    	
    }
}
?>