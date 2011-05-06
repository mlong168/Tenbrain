<?php

abstract class Provider_model extends CI_Model {
	
	protected $account_server_count_limits = array();
	
	function __construct(){
		parent::__construct();
		$this->account_server_count_limits = $this->load_account_server_count_limits();
	}
	
	private function load_account_server_count_limits()
	{
		$query = $this->db->get('account_server_count_limits');

		if($query->num_rows())
		{
			$server_limit = array();
			$server_count_limits = $query->result_array();

			foreach($server_count_limits as $limit)
			{
				$server_limit[$limit['provider']][$limit['roleid']] = $limit['count'];
			}
			return $server_limit;
		}
	}
	
	protected function get_deployed_server_count()
	{
		$this->load->model("Instance_model", "instance");
		$servers = $this->instance->get_user_instances();

		return count($servers);
	}
	
	protected function get_provider_instance_id($id)
	{
		$this->db->select('provider_instance_id')->from('user_instances')->where(array(
			'instance_id'	=> $id,
			'account_id'	=> $this->session->userdata('account_id')
		));
		$query = $this->db->get();
		
		return $query->num_rows === 1 ? $query->row()->provider_instance_id : false;
	}
	
	protected function view_backups($provider, $instance_id)
	{
		$this->load->model("Backup_model", "backup");
		
		return $this->backup->get_available_backups($provider, $instance_id);
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
	
	abstract public function list_images();
	abstract public function list_instances($ids);
	
// 	abstract public function launch_instance(array $params);*/

	abstract public function start_instances(array $ids);
	abstract public function stop_instances(array $ids);
	abstract public function reboot_instances(array $ids);
	abstract public function terminate_instances(array $ids);
	
	abstract public function create_load_balancer($name, array $instances, $gogrid_lb_address);
	abstract public function delete_load_balancer($id);
}

/* End of file provider.php */
/* Location: ./system/application/models/provider.php */