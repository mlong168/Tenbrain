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
	
	public function get_user_aws_credentials()
	{
		$credentials_model = new Application_Model_DbTable_Credentials_Amazon();
		$credentials = $credentials_model->get_credentials($this->user_id);
		
		return $credentials;
	}
	
	public function set_user_aws_credentials($key, $secret_key)
	{
		$aws_userid = $this->get_aws_userid($key, $secret_key);
		if(!$aws_userid)
		{
			$this->die_with_error("The security credentials you've provided do not seem to be valid. Please try again.");
		}
		
		$credentials_model = new Application_Model_DbTable_Credentials_Amazon();
		$credentials_model->set_credentials($this->user_id, array(
			'aws_user_id'	=> $aws_userid,
			'key'			=> $key,
			'secret_key'	=> $secret_key
		));
		
		return true;
	}
	
	private function get_aws_userid($key, $secret_key)
	{
		$iam_handle = new AmazonIAM($key, $secret_key);
		$response = $iam_handle->get_user();
		
		return $response->isOK()
			? (string) $response->body->UserId()->first()
			: false;
	}

	public function update_user_aws_credentials($new_credentials)
	{
		// do smth here!!!
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

	public function get_connection_params(array $params)
	{
		$response = $this->ec2->describe_instances(array('InstanceId' => $params['provider_server_id']));
		$this->test_response($response);
		
		$dns_name = (string) $response->body->dnsName()->first();
		
		return array(
			'hostname'	=> $dns_name,
			'username'	=> Zend_Auth::getInstance()->getIdentity()->username,
			'login_user'=> 'ubuntu',
			'port'		=> 22,
			'provider'	=> strtolower($this->name)
		);
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
	
	public function delete_backup($backup_id = false)
	{
		$backup_model = new Application_Model_Backups();
		$backup = $backup_model->get_backup_by_id($backup_id);
		
		if(!$backup_id) $this->die_with_error('No backup specified');

		$response = $this->ec2->delete_snapshot($backup['provider_backup_id']);
		$this->test_response($response);
		
		$backup_model->remove_backup($backup_id);
		return true;
	}
	
	private function get_backup_volume($backup_id = false)
	{
		if(!$backup_id) $this->die_with_error('No backup specified');

		$response = $this->ec2->describe_snapshots(array('SnapshotId' => $backup_id));
		$this->test_response($response);
		return (string) $response->body->volumeId()->first();
	}
	
	private function extract_tag_from_tagset($tagset, $tag_name)
	{
		return (string) $tagset->query("descendant-or-self::item[key='$tag_name']/value")->first();
	}
	
	private function restore_backup($provider_backup_id, $name = '', $type = NULL)
	{
		if($type == NULL)
		{
			$type = $this->default_type;
		}

		$server_model = new Application_Model_Servers();
		$backup_model = new Application_Model_Backups();
		
		$backup = $backup_model->get_backup_by_provider_id($provider_backup_id);
		$server = $server_model->get_user_server_by_provider_id($backup['server_id']);

		$response = $this->ec2->run_instances($server['image_id'], 1, 1, array(
			'KeyName'		=> $this->get_user_key_pair(),
			'InstanceType'	=> $type,

			'BlockDeviceMapping' => array(
				'DeviceName'				=> '/dev/sda',
				'Ebs.DeleteOnTermination'	=> true,
				'Ebs.SnapshotId'			=> $provider_backup_id
			)
		));
		$this->test_response($response);

		$new_server_id = $response->body->instanceId()->map_string();
		$new_server_id = $new_server_id[0];

		if(!empty($name))
		{
			$tag_response = $this->tag_server($new_server_id, "Name", $name);
		}
		
		// write to db if things went fine
		$server_model->add_server(array(
			'provider_server_id' 	=> $new_server_id,
			'name' 					=> $name,
			'provider' 				=> 'Amazon'
		));
		
		return array(
			'success'			=> true,
			'new_instance_id'	=> $new_server_id
		);
	}
	
	public function restore_backup_to_new_server($backup_id, array $settings)
	{
		$name = $settings['name'];
		$type = $settings['type'];
		
		if($type == NULL)
		{
			$type = $this->default_type;
		}
		$backup_model = new Application_Model_Backups();
		$backup = $backup_model->get_backup_by_id($backup_id);
		$this->restore_backup($backup['provider_backup_id'], $name, $type);
		return true;
	}
	
	public function restore_backup_to_corresponding_server($backup_id = false)
	{
		$server_model = new Application_Model_Servers();
		$backup_model = new Application_Model_Backups();
		$backup = $backup_model->get_backup_by_id($backup_id);
		$response = $this->ec2->describe_instances(array(
			'Filter' => array(
				array('Name' => 'block-device-mapping.volume-id', 'Value' => $this->get_backup_volume($backup['provider_backup_id']))
			)
		));
		$this->test_response($response);
		$old_server = $response->body->instancesSet();
		
		
		if(!$old_server) $this->die_with_error('The server this backup was created off has been terminated');

		$old_server = $old_server->first()->item;
		$old_server = array(
			'id'		=> (string) $old_server->instanceId,
			'type'		=> (string) $old_server->instanceType,
			'name'		=> $this->extract_tag_from_tagset($old_server->tagSet, 'Name')
		);
		$new = $this->restore_backup($backup['provider_backup_id'], $old_server['name'], $old_server['type']);
		if(!$new['success'])
		{
			$this->die_with_error('Sorry, a problem has occurred while restoring your backup');
		}
		
		$response = $this->ec2->terminate_instances($old_server['id']);
		$this->test_response($response);
		
		$server_ids = $server_model->get_server_ids($old_server['id']);
		$server_model->remove_server($server_ids[0]);

		return true;
	}
	
	public function get_backuped_server($backup_id = false, $describe = false)
	{		
		$backup_model = new Application_Model_Backups();
		$backup = $backup_model->get_backup_by_id($backup_id);
		$response = $this->ec2->describe_instances(array(
			'Filter' => array(
				array('Name' => 'block-device-mapping.volume-id', 'Value' => $this->get_backup_volume($backup['provider_backup_id'])),
			)
		));
		$this->test_response($response);
		$servers = array();
		if(count($response->body->item()) > 1)
		{
			$list = $response->body->instanceId();

			$results = $list->map(function($node){
				return $node->parent();
			});
			$results->each(function($node, $i, &$servers){
				$tags = $node->tagSet;
				$name = '<i>not set</i>';
				if($tags->count())
				{
					$name_ary = $tags->xpath("item[key='Name']/value");
					$name = (string) $name_ary[0];
				}
				$servers[] = array(
					'id'				=> $i,
					'name'				=> $name,
					'server_id'			=> (string) $node->instanceId,
					'dns_name'			=> (string) $node->dnsName,
					'ip'				=> (string) $node->ipAddress,
					'state'				=> (string) $node->instanceState->name
					// ''				=> (string) $node->,
				);
			}, $servers);
		}

		return $servers;
	}
	
	function get_backups($provider, $server_id)
	{
		return $this->view_backups($provider, $server_id);
	}
	
	public function create_backup($id, $name, $description = 'sample description')
	{
		$server_id = $this->get_provider_server_id($id);
		if(!$server_id) return false;
		
		$response = $this->ec2->describe_instances(array('InstanceId' => $server_id));
		$this->test_response($response);

		$server = $response->body->instancesSet()->first();
		if(!$server->count()) $this->die_with_error('The backup could not be created from an server yet');

		$server = $server->item;
		$image_id = (string) $server->imageId;

		$volume_id = $server->blockDeviceMapping->query('descendant-or-self::item[deviceName = "/dev/sda" or deviceName = "/dev/sda1"]/ebs/volumeId');
		if(!$volume_id->count()) $this->die_with_error('The backup could not be created from an server yet');
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

	private function get_elb_handle()
	{
		$credentials = $this->get_user_aws_credentials();
		if(empty($credentials))
			$this->die_with_error('You have to upgrade to TenBrain Premium and enter your own AWS credentials to use this feature');

		return new AmazonELB($credentials['key'], $credentials['secret_key']);
	}
	
	public function create_load_balancer($name, array $servers, $gogrid_lb_address)
	{
		$elb = $this->get_elb_handle();

		$response = $elb->create_load_balancer($name, array(
			array(
				'Protocol' => 'HTTP',
				'InstancePort' => 80,
				'LoadBalancerPort' => 80
			)
		), 'us-east-1d');
		$this->test_response($response);
		$balancer_model = new Application_Model_Balancer();
		$lb_id = $balancer_model->add_load_balancer(array(
			'name'				=> $name,
			'provider'			=> $this->name,
			'provider_lb_id'	=> $name,
			'dns_name'			=> $response->body->CreateLoadBalancerResult->DNSName,
		)); 
		
		$provider_server_ids = array_values($servers);
		$tb_server_ids = array_keys($servers);
		if($this->register_instances_within_load_balancer($name, $provider_server_ids))
		{
			$balancer_model->add_servers_to_lb($lb_id, $tb_server_ids);
		}

		return true;
	}
	
	public function delete_load_balancer($id, $tb_id)
	{
		$name = $id;
		$elb = $this->get_elb_handle();
		$response = $elb->delete_load_balancer($name);
		$this->test_response($response);
		
		$balancer_model = new Application_Model_Balancer();
		$balancer_model->delete_load_balancer($tb_id);
		
		return true;
	}
	
	public function register_instances_within_load_balancer($lb_name, $server_ids)
	{
		$enabled_zones = $this->get_lb_availability_zones($lb_name);		
		$instances_to_register = array();
		$zones_to_register = array();
		foreach($server_ids as $instance)
		{
			$response = $this->ec2->describe_instances(array('InstanceId' => $instance));
			$instance_zone = (string) $response->body->availabilityZone()->first();
			if(!in_array($instance_zone, $enabled_zones))
			{
				$zones_to_register []= $instance_zone;
			}
			$instances_to_register []= array('InstanceId' => $instance);
		}
		
		$elb = $this->get_elb_handle();
		if(count($zones_to_register))
		{
			$response = $elb->enable_availability_zones_for_load_balancer($lb_name, $zones_to_register);
		}
		
		$response = $elb->register_instances_with_load_balancer($lb_name, $instances_to_register);
		$this->test_response($response);
		return true;
	}
	
	private function get_lb_availability_zones($lb_name)
	{
		$elb = $this->get_elb_handle();
		
		$response = $elb->describe_load_balancers(array('LoadBalancerNames' => $lb_name));
		$zones_response = $response->body->AvailabilityZones();
		$zones = array();
		foreach($zones_response as $zone)
		{
			$zones []= (string) $zone->member;
		}
		return $zones;
	}
	
	public function get_elastic_ips()
	{
		$response = $this->ec2->describe_addresses();
		$list = $response->body->query('descendant-or-self::publicIp');		
		$results = $list->map(function($node){
			return $node->parent();
		});
		
		$ips = array();
		$ips['instances'] = array();
		$results->each(function($node, $i, &$ips){
			$instance_id = (string) $node->instanceId;
			$ip = (string) $node->publicIp;
			if(empty($instance_id))
			{
				$ips []= array(
					'address'		=> $ip,
					'instance'		=> '',
					'instance_dns'	=> ''
				);
			}
			else
			{			
				$ips['instances'] []= $instance_id;
				$ips[$instance_id] = $ip;
			}
		}, $ips);
		
		if(count($ips['instances']))
		{
			$response = $this->ec2->describe_instances(array('InstanceId' => $ips['instances']));
			$list = $response->body->query('descendant-or-self::instanceId');		
			$results = $list->map(function($node){
				return $node->parent();
			});
			
			$results->each(function($node, $i, &$ips){
				$id = (string) $node->instanceId;
				$name = $node->tagSet->xpath("descendant-or-self::item[key='Name']/value");
				$name = $name ? (string) $name[0] : '<i>not set</i>';
				$ips []= array(
					// 'address'		=> (string) $node->ipAddress,
					'address'		=> $ips[$id],
					'instance'		=> $name . ' (' . $id . ')',
					'instance_dns'	=> (string) $node->dnsName
				);
				unset($ips[$id]);
			}, $ips);
		}
		unset($ips['instances']);
		
		// set the proper ids:
		foreach($ips as $id => &$value) $value['id'] = $id;
		
		return $ips;
	}
	
	public function allocate_address()
	{
		$response = $this->ec2->allocate_address();
		return $response->isOK() ? (string) $response->body->publicIp : false;
	}
	
	public function get_short_instances_list()
	{
		$response = $this->ec2->describe_instances(array(
			'Filter' => array(
				array('Name' => 'instance-state-name', 'Value' => array('running'))
			)
		));
		$this->test_response($response);

		$instances = array();
		$list = $response->body->query("descendant-or-self::instanceId");
		$results = $list->map(function($node){
			return $node->parent();
		});

		$results->each(function($node, $i, &$instances){
			$name = $node->tagSet->xpath("descendant-or-self::item[key='Name']/value");
			$name = $name ? (string) $name[0] : '<i>not set</i>';
			$id = (string) $node->instanceId;
			$instances[] = array(
				'id'				=> $i,
				'instance_id'		=> $id,
				'instance_name'		=> $name . ' (' . $id . ')'
			);
		}, $instances);
		
		return $instances;
	}
	
	public function associate_ip($instance_id, $address)
	{
		$response = $this->ec2->associate_address($instance_id, $address);
		$this->test_response($response);
		
		return $response->isOK();
	}
	
	public function disassociate_ip($address)
	{
		$response = $this->ec2->disassociate_address($address);
		$this->test_response($response);
		
		return $response->isOK();
	}
	
	public function release_ip($addresses)
	{
		if(is_string($addresses)) $addresses = array($addresses);
		$success = true;
		foreach($addresses as $address)
		{
			$response = $this->ec2->release_address($address);
			if(!$response->isOK()) $success = false;
		}
		return $success;
	}
}