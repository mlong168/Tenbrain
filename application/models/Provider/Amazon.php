<?php

require_once APPLICATION_PATH . '/../library/Amazon/sdk.class.php';

class Application_Model_Provider_Amazon extends Application_Model_Provider
{

	const TENBRAIN_API_KEY = 'AKIAJHXE62BKBM2RJWBA';
	const TENBRAIN_API_SECRET = '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';
	const TENBRAIN_API_USER_ID = '528233608018';

	private $ec2;
	private $storage;
	
	private $available_types = array(
		't1.micro',
		'm1.small',
		'm1.large',
		'm1.xlarge',
		'm2.2xlarge',
		'm2.4xlarge', 
		'c1.medium',
		'c1.xlarge',
		'cc1.4xlarge', 
		'cg1.4xlarge'
	);
	private $default_type = 't1.micro';
	
	
	function __construct()
	{
		parent::__construct();
		
		$this->name = 'Amazon';
		$this->storage = new Application_Model_Servers();
		$this->ec2 = new AmazonEC2(self::TENBRAIN_API_KEY, self::TENBRAIN_API_SECRET);
	}

	private function test_response($response)
	{
		if(!$response->isOK())
		{
			$error = $response->body->Error();
			$this->die_with_error((string) $error[0]->Message);
		}
	}
	
	public function list_images()
	{
		$response = $this->ec2->describe_images(array('Owner' => array('self', self::TENBRAIN_API_USER_ID)));

		$images = array();
		if(!$response->isOK()) return array('success' => false, 'images' => $images);
		$list = $response->body->query("descendant-or-self::imageId");
		$results = $list->map(function($node){
			return $node->parent();
		});

		$results->each(function($node, $i, &$images){
			$images[] = array(
				'id'				=> $i,
				'provider'			=> 'Amazon',
				'image_id'			=> (string) $node->imageId,
				'name'				=> (string) $node->name,
				'state'				=> (string) $node->imageState,
				'description'		=> (string) $node->description,
				'virtualization'	=> (string) $node->virtualizationType,
				'location'			=> (string) $node->imageLocation
				// ''				=> (string) $node->,
			);
		}, $images);

		return $images;
	}

	private function get_user_key_pair()
	{
		$auth = Zend_Auth::getInstance();
		$username = $auth->getIdentity()->username;
		
		$response = $this->ec2->describe_key_pairs(array(
			'KeyName' => $username
		));
		if(!$response->isOK())
		{
			// create the key pair if one does not exist
			$this->create_user_key_pair();
		}
		return $username;
	}
	
	private function create_user_key_pair()
	{
		$auth = Zend_Auth::getInstance();
		$auth = $auth->getIdentity();
		$username = $auth->username;
		
		// for security - move to /tmp folder
		chdir('/tmp');
		$filename = sha1($username . time());
		exec("/usr/bin/ssh-keygen -N '' -f $filename");
		
		// private key file has the name as provided in the command
		$private_key = file_get_contents($filename);
		unlink($filename);
		
		// public key file is generated with '.pub' type
		$public_key_path = $filename . '.pub';
		$public_key = file_get_contents($public_key_path);
		unlink($public_key_path);
		
		// move back to normal execution
		chdir(APPLICATION_PATH);

		$response = $this->ec2->import_key_pair($username, $public_key);
		$this->test_response($response);
		
		// if we are here, that pretty much means we've succeeded to import public key to amazon, so let's write the private one to db
		$this->get_db_connection()->insert('account_key_pairs', array(
			'account_id'	=> $auth->id,
			'key_pair_name'	=> $username,
			'private_key'	=> $private_key
		));
		
		return true;
	}

	public function get_user_private_key($user_id)
	{
		$key = $this->get_db_connection()->fetchRow('SELECT `key_pair_name` AS `name`, `private_key` AS `key` FROM `account_key_pairs` WHERE account_id = ' . $user_id);
		return $key;
	}
	
	private function tag_server($server_id, $tag_name, $value)
	{
		$response = $this->ec2->create_tags($server_id, array(
			array('Key' => $tag_name, 'Value' => $value)
		));
		
		if(!$response->isOK())
		{
			// being here means the instance is not available to be tagged yet
			// give amazon-side a second to actually launch it
			sleep(1);
			$this->tag_server($server_id, $tag_name, $value);
		}
		
		return true;
	}
	
	public function get_available_server_types()
	{
		$premium = false;
		$reason = $premium ? '' : 'Not available in a free version';
		$output = array();
		
		foreach($this->available_types as $type)
		{
			$output []= array(
				'name'		=> $type,
				'value'		=> $type,
				'available'	=> $premium || $type === $this->default_type,
				'reason'	=> $reason
			);
		}
		
		return $output;
	}
	
	public function launch_server(array $params)
	{
		$image_id = $params['image_id'];
		$name = $params['name'];
		$type = $params['type'];
		$response = $this->ec2->describe_images(array(
			'ImageId' => $image_id
		));
		$this->test_response($response);
		$device_name = $response->body->query('descendant-or-self::deviceName')->first();

		$key_pair = $this->get_user_key_pair();
		$response = $this->ec2->run_instances($image_id, 1, 1, array(
			'KeyName'		=> $key_pair,
			'InstanceType'	=> $type,

			'BlockDeviceMapping' => array(
				'DeviceName'				=> (string) $device_name,
				'Ebs.DeleteOnTermination'	=> true
			)
		));
		
		$this->test_response($response);

		$instance_id = $response->body->instanceId()->first();
		$details = $instance_id->parent();
		$instance_id = (string) $instance_id;
		
		$this->tag_server($instance_id, 'Name', $name);

		$this->storage->add_server(array(
			// common to all providers
			'name'				=> $name,
			'provider_server_id'=> $instance_id,
			'type'				=> $type,
			'image_id'			=> $image_id,
			'provider'			=> $this->name,
			
			// amazon-specific data:
			'launch_time'			=> $details->launchTime,
			'key_name'				=> (string) $details->keyName,
			'availability_zone'		=> (string) $details->placement->availabilityZone,
			'kernel_id'				=> (string) $details->kernelId,
			'monitoring'			=> (string) $details->monitoring->state,
			'security_group_id'		=> (string) $details->groupSet->groupId()->first(),
			'security_group_name'	=> (string) $details->groupSet->groupName()->first(),
			'root_device_type'		=> (string) $details->rootDeviceType,
			'root_device_name'		=> (string) $details->rootDeviceName,
			'virtualization_type'	=> (string) $details->virtualizationType,
			'hypervisor'			=> (string) $details->hypervisor
			// ''	=> (string) $details->,
		));
		return true;
	}
	
	public function list_servers($ids = array(), $state = 'running')
	{
		$possible_states = array(
			'running'	=>array('pending', 'running', 'stopping'),
			'stopped'	=>array('stopped', 'stopping', 'shutting-down')
		);
		$response = $this->ec2->describe_instances(array(
			'InstanceId' => array_keys($ids)
		));
		// $this->test_response($response);

		$instances = array();
		$list = $response->body->query("descendant-or-self::instanceId")->map(function($node){
			return $node->parent();
		})->each(function($node) use(&$instances, $ids, $state, $possible_states){
			$server_state = (string) $node->instanceState->name;
			if(in_array($server_state, $possible_states[$state]))
			{
				$name = $node->tagSet->xpath("descendant-or-self::item[key='Name']/value");
				$name = $name ? (string) $name[0] : '<i>not set</i>';
				
				$id = (string) $node->instanceId;
				$instances[] = array(
					'id'				=> $ids[$id],
					'name'				=> $name,
					'dns_name'			=> (string) $node->dnsName,
					'ip_address'		=> (string) $node->ipAddress,
					'image_id'			=> (string) $node->imageId,
					'state'				=> $server_state,
					'type'				=> (string) $node->instanceType,
					'provider'			=> 'Amazon'
					// ''				=> (string) $node->,
				);
			}
		});
		// print_r($instances);
		
		return $instances;
	}

	public function get_connection_info(array $params)
	{
		$response = $this->ec2->describe_instances(array('InstanceId' => $params['provider_server_id']));
		$this->test_response($response);
		
		$dns_name = (string) $response->body->dnsName()->first();
		
		$info = 'You have to download key file to connect to the server by ssh';
		$info .= '<br />This can be done by right-clicking on the server -> Connect -> Download key file';
		$info .= '<br />After that, use the following command under UNIX shell:';
		$info .= "<br />ssh -i {$params['key_name']}.pem root@$dns_name";
		
		return $info;
	}

	public function start_servers(array $ids)
	{
		$response = $this->ec2->start_instances($ids);
		$this->test_response($response);
		return true;
	}
	
	public function stop_servers(array $ids)
	{
		$response = $this->ec2->stop_instances($ids);
		$this->test_response($response);
		return true;
	}
	
	public function reboot_servers(array $ids)
	{
		$response = $this->ec2->reboot_instances($ids);
		$this->test_response($response);
		return true;
	}
	
	public function terminate_servers(array $ids)
	{
		$response = $this->ec2->terminate_instances(array_values($ids));
		$this->test_response($response);

		$this->storage->remove_servers(array_keys($ids));
		return true;
	}
	
	public function modify_server($server_id, $type, $tb_server_id, $all_params)
	{
		if(in_array($type, $this->available_types))
		{
			$response = $this->ec2->modify_instance_attribute(
				$server_id,
				'instanceType',
				array('Value' => $type)
			);
			$this->test_response($response);
			
			$all_params['type'] = $type;
			$this->storage->change_server($tb_server_id, $all_params);
			return true;
		}
		return false;
	}
	
	public function create_load_balancer($name, array $instances, $gogrid_lb_address)
	{
		
	}
	
	public function delete_load_balancer($id)
	{
		
	}
	
	function get_backups($provider, $instance_id)
	{
		return $this->view_backups($provider, $instance_id);
	}
	
	public function create_backup($id, $name, $description = 'sample description')
	{
		$server_id = $this->get_provider_server_id($id);
		if(!$server_id) return false;
		
		$response = $this->ec2->describe_instances(array('InstanceId' => $server_id));
		$this->test_response($response);

		$server = $response->body->instancesSet()->first();
		if(!$server->count()) $this->die_with_error('The backup could not be created from an instance yet');

		$server = $server->item;
		$image_id = (string) $server->imageId;

		$volume_id = $server->blockDeviceMapping->query('descendant-or-self::item[deviceName = "/dev/sda" or deviceName = "/dev/sda1"]/ebs/volumeId');
		if(!$volume_id->count()) $this->die_with_error('The backup could not be created from an instance yet');
		$volume_id = (string) $volume_id->first();

		$response = $this->ec2->create_snapshot($volume_id, $description);
		$this->test_response($response);

		$snap_id = $response->body->snapshotId()->map_string();
		$snap_id = $snap_id[0];

		$tag_response = $this->tag_server($snap_id, "Name", $name);
		
		$backup_model = new Application_Model_Backups();
		
		$backup_image = array(
			'name' => $name,
			'provider_backup_id' => $snap_id,
			'provider' => 'Amazon',
			'description' => $description,
		
			'server_id' => $server_id
		);
		
		$backup_model->add_backup($backup_image);

		return true;
	}
	
	public function created_backups()
	{
		$backup_model = new Application_Model_Backups();
		$backups = $backup_model->get_available_backups($this->name);
		//foreach($backups as $i => $backup)
		//{
			//$backup['status'] = 'deleted';
			//$backup['status'] = $this->get_backup_status($backup['provider_backup_id']);
			//$backups[$i] = $backup;
		//}
		
		return $backups;
	}
}