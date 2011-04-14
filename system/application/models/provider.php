<?php

abstract class Provider extends Model {
	
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
	
	abstract public function list_images();
	abstract public function list_instances($ids);
// 	abstract public function launch_instance($params);*/
// 	abstract public function delete_instance($id);*/
	abstract public function start_instance($id);
	abstract public function stop_instance($id);
// 	abstract public function restart_instance($id);*/
}

/* End of file provider.php */
/* Location: ./system/application/models/provider.php */