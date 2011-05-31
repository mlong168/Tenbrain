<?php

abstract class Application_Model_Provider
{
	public $name;
	protected $account_server_count_limits = array();
	
	protected function __construct()
	{
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

	abstract public function start_servers(array $ids);
	abstract public function stop_servers(array $ids);
	abstract public function reboot_servers(array $ids);
	abstract public function terminate_servers(array $ids);
	
	abstract public function create_load_balancer($name, array $instances, $gogrid_lb_address);
	abstract public function delete_load_balancer($id);
}