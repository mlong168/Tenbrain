<?php

abstract class Application_Model_Provider
{
	public $name;
	protected $user_id;
	protected $account_server_count_limits = array();
	
	protected function __construct()
	{
		$auth = Zend_Auth::getInstance()->getIdentity();
		if(!$auth)
		{
			$this->die_with_error('Please relogin');
		}
		$this->user_id = $auth->id;
		$this->account_server_count_limits = $this->load_account_server_count_limits();
	}
	
	private function load_account_server_count_limits()
	{
		return 1;
	}
	
	protected function get_deployed_server_count()
	{

	}
	
	protected function get_provider_server_id($id)
	{
		$server_model = new Application_Model_Servers();
		$server = $server_model->get_user_server_provider_ids(array($id));

		return $server[$this->name][0];
	}
	
	protected function view_backups($provider, $instance_id)
	{
		$backup_model = new Application_Model_Backups();
		
		return $backup_model->get_available_backups($provider, $instance_id);
	}
	
	protected function die_with_error($error_message)
	{
		header('Content-type: application/json');
		echo json_encode(array(
			'error'			=> true,
			'error_message'	=> $error_message
		));
		die; // how can you proceed if things failed? ;)
	}
	
	protected function get_db_connection()
	{
		$config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', 'production');
		return Zend_Db::factory($config->get('resources')->get('db'));
	}
	
	abstract public function launch_server(array $params);
	
	abstract public function list_images();
	abstract public function list_servers($ids, $state);

	/**
	* @param $params: server's database record
	*/
	abstract public function get_connection_info(array $params);
	
	abstract public function start_servers(array $ids);
	abstract public function stop_servers(array $ids);
	abstract public function reboot_servers(array $ids);
	abstract public function terminate_servers(array $ids);
	
	abstract public function modify_server($provider_server_id, $new_type, $tb_server_id, $all_params);
	
	abstract public function create_load_balancer($name, array $instances, $gogrid_lb_address);
	abstract public function delete_load_balancer($id);
}