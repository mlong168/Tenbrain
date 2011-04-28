<?php

abstract class Provider_model extends Model {
	
	function __construct(){
		parent::__construct();
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