<?php
/**
 * GogridController
 */

class GogridController extends Zend_Controller_Action
{
	private $gg;
	
	function init()
	{	
		
		$helper = $this->_helper->getHelper('Layout');
		$this->layout =$helper->getLayoutInstance();
		$this->_helper->viewRenderer->setNoRender();
		$this->layout->disableLayout();
		header('Content-type: application/json');
		
		$this->gg = new Application_Model_Provider_GoGrid();
		
		if(!Zend_Auth::getInstance()->hasIdentity())
		{
			echo json_encode(array(
				'success'	=> false,
				'message'	=> 'you do not have the permission to access this page'
			));
			die();
		}
	}
	
    public function indexAction ()
    {
        // TODO Auto-generated GoGridController::indexAction() default action
    }
    
	function get_user_credentials()
	{
		echo json_encode(array(
			'success'	=> true,
			'credentials'		=> $this->gg->get_user_gogrid_credentials()
		));
	}
	
	function set_user_credentials()
	{
		$new_credentials = array();
		$new_credentials['key'] = $this->input->post('key');
		$new_credentials['secret_key'] = $this->input->post('secret_key');
		
		$credentials = $this->gg->get_user_gogrid_credentials();
		
		$result = $credentials 
			? $this->gg->update_user_gogrid_credentials($new_credentials)
			: $this->gg->set_user_gogrid_credentials($new_credentials);
			
		echo json_encode($result);
	}
	
	function lookup($lookup)
	{
		print_r($this->gg->lookup($lookup));
	}
	
	function getFreeAddressesAction()
	{
		$addresses = $this->gg->get_free_addresses();
		echo json_encode(array(
			'success'	=> (bool) $addresses,
			'addresses'	=> $addresses
		));
	}
	
	function getAvailableRamSizesAction()
	{
		$rams = $this->gg->get_available_ram_sizes();
		echo json_encode(array(
			'success'	=> (bool) $rams,
			'sizes'		=> $rams
		));
	}
	
	function launchInstanceAction()
	{
		// TODO: ACL
		//$roleid = $this->acl->get_user_role_id();
		//$allow_launch = $this->gg->allow_launch_instance($roleid);
		//if(!$allow_launch)
		//{
		//	echo json_encode(array(
		//		'success' => false
		//	));
		//	die;
		//}
		
		$params = array(
			'image'			=> $this->getRequest()->getParam('image_id'),
			'name'			=> $this->getRequest()->getParam('name'),
			'ip'			=> $this->getRequest()->getParam('address'),
			'server.ram'	=> $this->getRequest()->getParam('ram')
		);
		
		echo json_encode(array(
			'success' => $this->gg->launch_server($params)
		));
	}
	
	function getInstancePasswordAction()
	{
		$instance_id = $this->input->post('instance_id');
		$success = $password = false;
		if($instance_id)
		{	
			$password = $this->gg->get_password($instance_id);
			$success = !empty($password);
		}
		
		echo json_encode(array(
			'success'		=> $success,
			'error_message'	=> $success ? '' : 'The password for this server could not be retrieved yet. Pease wait until the server finishes deploying',
			'username'		=> $success ? $password['username'] : '',
			'password'		=> $success ? $password['password'] : '',
		));
	}
	
	function get_instances_for_lb()
	{
		$instances = $this->gg->get_instances_for_lb();
		echo json_encode(array(
			'success'	=> true,
			'instances'	=> $instances
		));
	}
	
	function create_load_balancer()
	{
		echo json_encode(array(
			'success' => $this->gg->create_load_balancer(
				$this->input->post('name'),
				$this->input->post('address'),
				$this->input->post('instances')
			)
		));
	}
	
	function delete_load_balancer()
	{
		echo json_encode(array(
			'success' => $this->gg->delete_load_balancer($this->input->post('id'))
		));
	}
	
	function create_backup($id,$name,$desc)
	{
		echo json_encode(array(
			'success' => $this->gg->create_backup(
				//$this->input->post('instance_id'),
				//$this->input->post('name'),
				//$this->input->post('description')
				$id,$name,$desc
			)
		));
	}
	
	function created_backups()
	{
		echo json_encode(array(
			'success' => $this->gg->created_backups()
		));
	}
	
	function delete_backup()
	{
		echo json_encode(array(
			'success' => $this->gg->delete_backup($this->input->post('backup_id'))
		));
	}
	
	function backup_instance()
	{
		echo json_encode($this->gg->describe_backup_instance($this->input->post('backup_id')));
	}
	
	function restore_backup_to_corresponding_instance()
	{
		echo json_encode(array(
			'success' => $this->gg->restore_backup_to_corresponding_instance($this->input->post('backup_id'))
		));
	}
	
	function restore_backup_to_new_instance()
	{
		$instance = array(
			'backup_id'	=> $this->input->post('backup_id'),
			'name'	=>	$this->input->post('name'),
			'ram'	=>	$this->input->post('ram')
		);

		echo json_encode(array(
			'success' => $this->gg->restore_backup_to_new_instance($instance)
		));
	}
	
	function get_backup_status($id)
	{
		echo json_encode(array(
			'success' => $this->gg->get_backup_status($id)
		));
	}
}
