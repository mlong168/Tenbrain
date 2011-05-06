<?php

include_once('provider_model.php');

class Amazon_model extends Provider_model {

	private $ec2;
	private $username;
	private $premium;
	
	public $name = 'Amazon';
	public $available_types = array('t1.micro', 
									'm1.small', 
									'm1.large', 
									'm1.xlarge', 
									'm2.2xlarge', 
									'm2.4xlarge', 
									'c1.medium', 
									'c1.xlarge', 
									'cc1.4xlarge', 
									'cg1.4xlarge');
	public $default_type = 't1.micro';
	
	const API_KEY	=	'AKIAIZPNXU6QOZESFUGQ';
	const API_SECRET	=	'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';
	const API_USER_ID	=	'808560206295';
	
	
	function __construct()
	{
		parent::__construct();

		$this->load->helper('amazon_sdk/sdk');

		$credentials = $this->get_user_aws_credentials();

		$this->ec2 = $credentials
			? new AmazonEC2($credentials['key'], $credentials['secret_key'])
			: new AmazonEC2(self::API_KEY, self::API_SECRET);

		$this->premium = !empty($credentials);
		$this->username = $this->authentication->is_signed_in()
			? $this->account_model->get_by_id($this->session->userdata('account_id'))->username
			: 'anonymous';
			
	}
	
	public function get_user_aws_credentials()
	{
		$credentials = array();
		$this->db->select('user_id, key, secret_key')
			->from('account_aws_credentials')
			->where('account_id', $this->session->userdata('account_id'));

		$query = $this->db->get();
		if(!$this->db->count_all_results()) return false;
		foreach ($query->result() as $row)
		{
			$credentials = array(
				'user_id'		=> $row->user_id,
				'key'			=> $row->key,
				'secret_key'	=> $row->secret_key
			);
		}

		return $credentials;
	}

	private function validate_credentials($new_credentials)
	{
		$user_id = $this->get_aws_user_id($new_credentials);
		if(!$user_id) 
			return false;
		
		return true;
	}
	
	private function get_aws_user_id($credentials)
	{
		$amaz_handle = new AmazonIAM($credentials['key'], $credentials['secret_key']);
		$response = $amaz_handle->get_user();
		if($response->isOK()) 
			return (string) $response->body->GetUserResult->User->UserId;
	}
	
	public function set_user_aws_credentials($new_credentials)
	{
		$this->db->set('account_id', $this->session->userdata('account_id'));
		$this->db->set('key', $new_credentials['key']);
		$this->db->set('secret_key', $new_credentials['secret_key']);
		
		$valid = $this->validate_credentials($new_credentials);
		if(!$valid)
			return array(
					'success'	=> false,
					'error_message'	=> "The security credentials you've provided do not seem to be valid. Please try again."
				);

		$new_credentials['user_id'] = $this->get_aws_user_id($new_credentials);
		
		$this->db->set('user_id', $new_credentials['user_id']);
		$this->db->insert('account_aws_credentials');

		return array(
				'success'	=> true
			);
	}

	public function update_user_aws_credentials($new_credentials)
	{
		$valid = $this->validate_credentials($new_credentials);
		if(!$valid)
			return array(
					'success'	=> false,
					'error_message'	=> "The security credentials you've provided do not seem to be valid. Please try again."
				);
				
		$this->db->where('account_id', $this->session->userdata('account_id'));
		$this->db->update('account_aws_credentials', $new_credentials);

		return array(
				'success'	=> true
			);
	}

	public function get_account_type()
	{
		$credentials = $this->get_user_aws_credentials();
		if(!$credentials) return 'basic';
		return 'premium';
	}

	private function test_response($response)
	{
		if(!$response->isOK())
		{
			$error = $response->body->Error();
			$this->die_with_error((string) $error[0]->Message);
		}
	}

	private function extract_tag_from_tagset($tagset, $tag_name)
	{
		return (string) $tagset->query("descendant-or-self::item[key='$tag_name']/value")->first();
	}
	
	/*
	 * common for provider-specific models
	 * $input_ary is an indexed array that contains associative of the form:
	 * 'id'				- db id of the instance
	 * 'instance_id'	- provider-specific instance id
	 * 'instance_name'	- instance name
	 */
	public function list_instances($input_ary, $state = 'running')
	{
		$response = $this->ec2->describe_instances(array(
			'InstanceId' => array_keys($input_ary)
		));
		$this->test_response($response);

		$instances = array();
		$list = $response->body->query("descendant-or-self::instanceId")->map(function($node){
			return $node->parent();
		})->each(function($node) use(&$instances, $input_ary){
			$name = $node->tagSet->xpath("descendant-or-self::item[key='Name']/value");
			$name = $name ? (string) $name[0] : '<i>not set</i>';
			$id = (string) $node->instanceId;
			$instances[] = array(
				'id'				=> $input_ary[$id],
				'name'				=> $name,
				'dns_name'			=> (string) $node->dnsName,
				'ip_address'		=> (string) $node->ipAddress,
				'image_id'			=> (string) $node->imageId,
				'state'				=> (string) $node->instanceState->name,
				'type'				=> (string) $node->instanceType,
				'provider'			=> 'Amazon'
				// 'virtualization'	=> (string) $node->virtualizationType,
				// 'root_device'	=> (string) $node->rootDeviceType,
				// ''				=> (string) $node->,
			);
		});
		
		return $instances;
	}

	public function describe_instances($state)
	{
		$filter = array();
		if($state === 'running')
		{
			$filter []= array('Name' => 'instance-state-name', 'Value' => array('running', 'pending', 'shutting-down', 'stopping'));
		}
		else if($state === 'stopped')
		{
			$filter []= array('Name' => 'instance-state-name', 'Value' => array('stopped', 'pending', 'shutting-down'));
		}
		else
		{
			$filter []= array('Name' => 'instance-state-name', 'Value' => $state);
		}

		if(!$this->premium) $filter []= array('Name' => 'key-name', 'Value' => $this->username);

		$response = $this->ec2->describe_instances(array('Filter' => $filter));
		$this->test_response($response);

		$instances = array();
		$list = $response->body->query("descendant-or-self::instanceId");
		$results = $list->map(function($node){
			return $node->parent();
		});

		$results->each(function($node, $i, &$instances){
			$name = $node->tagSet->xpath("descendant-or-self::item[key='Name']/value");
			$name = $name ? (string) $name[0] : '<i>not set</i>';
			$instances[] = array(
				'id'				=> $i,
				'name'				=> $name,
				'dns_name'			=> (string) $node->dnsName,
				'ip_address'		=> (string) $node->ipAddress,
				'instance_id'		=> (string) $node->instanceId,
				'image_id'			=> (string) $node->imageId,
				'state'				=> (string) $node->instanceState->name,
				'virtualization'	=> (string) $node->virtualizationType,
				'type'				=> (string) $node->instanceType,
				'root_device'		=> (string) $node->rootDeviceType,
				'provider'			=> 'Amazon'
				// ''				=> (string) $node->,
			);
		}, $instances);

		// for testing periodical list updater:
		// $instances[] = array(
			// 'id'				=> 2,
			// 'name'				=> (string) 'test',
			// 'dns_name'			=> (string) 'test.com',
			// 'ip_address'		=> (string) '192.168.2.5',
			// 'instance_id'		=> (string) 'fsdfsda',
			// 'image_id'			=> (string) 'fsfadfsd',
			// 'state'				=> (string) 'pending',
			// 'virtualization'	=> (string) 'shitirtual',
			// 'type'				=> (string) 't1.macro',
			// 'root_device'		=> (string) 'cbs'
		// );

		return array(
			'success'	=> true,
			'instances'	=> $instances
		);
	}

	public function describe_images()
	{
		$response = $this->ec2->describe_images(array('Owner' => array('self', '528233608018')));

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

		return array(
			'success'	=> true,
			'images'	=> $images
		);
	}

	public function list_images()
	{
		$response = $this->ec2->describe_images(array('Owner' => array('self', '528233608018')));

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
	
	private function create_user_key_pair()
	{
		// for security - move to /tmp folder
		chdir('/tmp');
		$filename = sha1($this->username . time());
		exec("/usr/bin/ssh-keygen -N '' -f $filename");
		
		// private key file has the name as provided in the command
		$private_key = file_get_contents($filename);
		unlink($filename);
		
		// public key file is generated with '.pub' type
		$public_key_path = $filename . '.pub';
		$public_key = file_get_contents($public_key_path);
		unlink($public_key_path);
		
		// move back to normal execution
		chdir(FCPATH);
		
		$response = $this->ec2->import_key_pair($this->username, $public_key);
		$this->test_response($response);
		
		// if we are here, that pretty much means we've succeeded to import public key to amazon, so let's write the private one to db
		$this->db->set('account_id', $this->session->userdata('account_id'));
		$this->db->set('key_pair_name', $this->username);
		$this->db->set('private_key', $private_key);
		$this->db->insert('account_key_pairs');
		
		return true;
	}
	
	private function update_user_key_pair($private_key)
	{
		$this->db->where('account_id', $this->session->userdata('account_id'));
		$this->db->update('account_key_pairs', array(
			'private_key' => $private_key
		));
	}

	private function get_user_key_pair()
	{
		$username = $this->username;
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
	
	public function download_user_private_key()
	{
		$key_name = '';
		$private_key = '';
		$this->db->select('key_pair_name, private_key')
			->from('account_key_pairs')
			->where('account_id', $this->session->userdata('account_id'));

		$query = $this->db->get();
		if(!$this->db->count_all_results()) return false;
		foreach ($query->result() as $row)
		{
			$key_name = $row->key_pair_name;
			$private_key = $row->private_key;
		}
		
		return array(
			'key_name'		=> $key_name,
			'private_key'	=> $private_key
		);
	}
	
	private function tag_instance($instance_id, $tag_name, $value)
	{
		$response = $this->ec2->create_tags($instance_id, array(
			array('Key' => $tag_name, 'Value' => $value)
		));
		
		if(!$response->isOK())
		{
			// being here means the instance is not available to be tagged yet
			// give amazon-side a second to actually launch it
			sleep(1);
			$this->tag_instance($instance_id, $tag_name, $value);
		}
		
		return true;
	}
	
	public function get_available_instance_types()
	{
		$reason = $this->premium ? '' : 'Not available in a free version';
		$types = $this->available_types;
		$output = array();
		
		foreach($types as $type)
		{
			$output []= array(
				'name'		=> $type,
				'available'	=> $this->premium || $type === $this->default_type,
				'reason'	=> $reason
			);
		}
		
		return $output;
	}
	
	public function get_available_server_types()
	{
		$types = array(); $i = 0; $premium = $this->premium;
		foreach($this->available_types as $type)
		{
			$available = $this->premium ? true : $type === $this->default_type;
			$types []= array(
				'id'		=> $i,
				'value'		=> $type,
				'name'		=> $type,
				'available'	=> $available,
				'reason'	=> $available ? '' : 'Not available in a free version'
			);
			++$i;
		}
		
		return $types;
	}
	
	public function allow_launch_instance($roleid)
	{
		$loaded_servers_count = (int)$this->get_deployed_server_count();
		$available_server_count = (int)$this->account_server_count_limits[$this->name][$roleid];
		
		return ($available_server_count > $loaded_servers_count);
	}
	
	public function launch_instance($image_id, $type, $name)
	{
		$this->load->model('Instance_model', 'instance');
		
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

		$instance_id = $response->body->instanceId();
		$instance_id = (string) $instance_id[0];
		
		$this->tag_instance($instance_id, 'Name', $name);
		
		// write to db if things went fine
		$this->instance->add_user_instance(array(
			'account_id' => $this->session->userdata('account_id'),
			'provider_instance_id' => $instance_id,
			'instance_name' => $name,
			'provider' => 'Amazon'
		));
		return true;
	}
	
	function modify_instance($instance_id, $type)
	{
		if(in_array($type, $this->available_types))
		{
			$response = $this->ec2->modify_instance_attribute($instance_id, 
															'instanceType', 
															array('Value' => $type)
			);
			$this->test_response($response);
			return true;
		}
		return false;
	}

	public function start_instances(array $ids)
	{
		$response = $this->ec2->start_instances($ids);
		$this->test_response($response);
		return true;
	}
	
	public function stop_instances(array $ids)
	{
		$response = $this->ec2->stop_instances($ids);
		$this->test_response($response);
		return true;
	}
	
	public function reboot_instances(array $ids)
	{
		$response = $this->ec2->reboot_instances($ids);
		$this->test_response($response);
		return true;
	}
	
	public function terminate_instances(array $ids)
	{
		$response = $this->ec2->terminate_instances($ids);
		$this->test_response($response);
		
		$this->load->model('Instance_model', 'instance');
		$this->instance->terminate_instances($ids, $this->session->userdata('account_id'));
		return true;
	}

	private function get_instance_volume($instance_id)
	{
		$response = $this->ec2->describe_instance_attribute($instance_id, 'blockDeviceMapping');
		$this->test_response($response);

		$volume_id = $response->body->volumeId();
		$volume_id = (string) $volume_id[0];

		return $volume_id;
	}

	public function created_backups($instance_id = false)
	{
		$this->load->model('Instance_model', 'instance');
		$this->load->model('Backup_model', 'backup');
		
		if($instance_id)
		{
			$instance_id = $this->get_provider_instance_id($instance_id);
			// if(!$instance_id) return false;
		}
		$filter = array(
			array('Name' => 'tag:User', 'Value' => $this->username)
		);
		if($instance_id)
		{
			$filter []= array('Name' => 'volume-id', 'Value' => $this->get_instance_volume($instance_id));
		}
		$response = $this->ec2->describe_snapshots(array(
			'Owner'		=> 'self',
			'Filter'	=> $filter
		));
		$this->test_response($response);
		$backups = array();
		$i = 0;
		foreach($response->body->snapshotSet->item as $node)
		{
			$backup = $this->backup->get_backup_by_provider_id($node->snapshotId[0]);
			$time = (string) $node->startTime;
			$time = date('Y-m-d H:i', strtotime($time));
			$backups[] = array(
				'id'				=> $backup->backup_id,
				'name'				=> $this->extract_tag_from_tagset($node->tagSet, 'Name'),
				'description'		=> (string) $node->description,
				'status'			=> (string) $node->status,
				'provider'			=> $this->name,
				'created_on'			=> $time
				// ''				=> (string) $node->,
			);
			$i++;
		}

		return $backups;
	}

	function get_backups($provider, $instance_id)
	{
		return $this->view_backups($provider, $instance_id);
	}

	private function get_backup_volume($backup_id = false)
	{
		if(!$backup_id) $this->die_with_error('No backup specified');

		$response = $this->ec2->describe_snapshots(array('SnapshotId' => $backup_id));
		$this->test_response($response);

		return (string) $response->body->volumeId()->first();
	}

	public function get_backuped_instance($backup_id = false, $describe = false)
	{		
		$this->load->model('Backup_model', 'backup');
		$backup = $this->backup->get_backup_by_id($backup_id);
		
		$response = $this->ec2->describe_instances(array(
			'Filter' => array(
				array('Name' => 'block-device-mapping.volume-id', 'Value' => $this->get_backup_volume($backup->provider_backup_id)),
			)
		));
		$this->test_response($response);

		$instances = array();
		if(count($response->body->item()) > 1)
		{
			$list = $response->body->instanceId();

			$results = $list->map(function($node){
				return $node->parent();
			});
			$results->each(function($node, $i, &$instances){
				$tags = $node->tagSet;
				$name = '<i>not set</i>';
				if($tags->count())
				{
					$name_ary = $tags->xpath("item[key='Name']/value");
					$name = (string) $name_ary[0];
				}
				$instances[] = array(
					'id'				=> $i,
					'name'				=> $name,
					'instance_id'		=> (string) $node->instanceId,
					'dns_name'			=> (string) $node->dnsName,
					'ip'		=> (string) $node->ipAddress,
					'state'				=> (string) $node->instanceState->name
					// ''				=> (string) $node->,
				);
			}, $instances);
		}

		return $instances;
	}

	public function describe_backup_instance($backup_id = false)
	{
		if(!$backup_id) $this->die_with_error('No backup specified');

		$instances = $this->get_backuped_instance($backup_id, true);
		return array(
			'success'	=> true,
			'instances'	=> $instances
		);
	}

	public function create_backup($id, $name, $description = 'sample description')
	{
		$this->load->model('Instance_model', 'instance');
		
		$instance_id = $this->get_provider_instance_id($id);
		if(!$instance_id) return false;
		
		$response = $this->ec2->describe_instances(array('InstanceId' => $instance_id));
		$this->test_response($response);

		$instance = $response->body->instancesSet()->first();
		if(!$instance->count()) $this->die_with_error('The backup could not be created from an instance yet');

		$instance = $instance->item;
		$image_id = (string) $instance->imageId;

		$volume_id = $instance->blockDeviceMapping->query('descendant-or-self::item[deviceName = "/dev/sda" or deviceName = "/dev/sda1"]/ebs/volumeId');
		if(!$volume_id->count()) $this->die_with_error('The backup could not be created from an instance yet');
		$volume_id = (string) $volume_id->first();

		$response = $this->ec2->create_snapshot($volume_id, $description);
		$this->test_response($response);

		$snap_id = $response->body->snapshotId()->map_string();
		$snap_id = $snap_id[0];

		$tag_response = $this->tag_instance($snap_id, "Name", $name);
		$tag_response = $this->tag_instance($snap_id, "User", $this->username);
		$tag_response = $this->tag_instance($snap_id, "ImageId", $image_id);
		
		$this->load->model("Backup_model","backup");
		
		$backup_image = array(
			'backup_name' => $name,
			'instance_id' => $instance_id,
			'provider_backup_id' => $snap_id,
			'provider' => 'Amazon',
			'description' => $description
		);
		
		$this->backup->add_backup($backup_image);

		return true;
	}

	public function delete_backup($backup_id = false)
	{
		$this->load->model("Backup_model","backup");
		$backup = $this->backup->get_backup_by_id($backup_id);
		
		if(!$backup_id) $this->die_with_error('No backup specified');

		$response = $this->ec2->delete_snapshot($backup->provider_backup_id);
		$this->test_response($response);
		
		$this->backup->remove_backup($backup->provider_backup_id);
		return true;
	}

	/*
	 * restores backup to new instance
	 */
	private function restore_backup($provider_backup_id, $name = '', $type = NULL)
	{
		if($type == NULL)
		{
			$type = $this->default_type;
		}

		$this->load->model('Instance_model', 'instance');
		
		$response = $this->ec2->describe_snapshots(array('SnapshotId' => $provider_backup_id));

		$this->test_response($response);

		$tags = $response->body->tagSet()->first();
		$image_id = $this->extract_tag_from_tagset($tags, 'ImageId');

		$response = $this->ec2->run_instances($image_id, 1, 1, array(
			'KeyName'		=> $this->get_user_key_pair(),
			'InstanceType'	=> $type,

			'BlockDeviceMapping' => array(
				'DeviceName'				=> '/dev/sda',
				'Ebs.DeleteOnTermination'	=> true,
				'Ebs.SnapshotId'			=> $provider_backup_id
			)
		));
		$this->test_response($response);

		$new_instance_id = $response->body->instanceId()->map_string();
		$new_instance_id = $new_instance_id[0];

		if(!empty($name))
		{
			$tag_response = $this->tag_instance($new_instance_id, "Name", $name);
		}
		
		// write to db if things went fine
		$this->instance->add_user_instance(array(
			'account_id' => $this->session->userdata('account_id'),
			'provider_instance_id' => $new_instance_id,
			'instance_name' => $name,
			'provider' => 'Amazon'
		));
		
		return array(
			'success'			=> true,
			'new_instance_id'	=> $new_instance_id
		);
	}

	public function restore_backup_to_corresponding_instance($backup_id = false)
	{
		$this->load->model('Instance_model', 'instance');
		$this->load->model('Backup_model', 'backup');
		$backup = $this->backup->get_backup_by_id($backup_id);
		$response = $this->ec2->describe_instances(array(
			'Filter' => array(
				array('Name' => 'block-device-mapping.volume-id', 'Value' => $this->get_backup_volume($backup->provider_backup_id))
			)
		));
		$this->test_response($response);
		$old_instance = $response->body->instancesSet();
		if(!$old_instance) $this->die_with_error('The instance this backup was created off has been terminated');

		$old_instance = $old_instance->first()->item;
		$old_instance = array(
			'id'		=> (string) $old_instance->instanceId,
			'type'		=> (string) $old_instance->instanceType,
			'name'		=> $this->extract_tag_from_tagset($old_instance->tagSet, 'Name')
		);
		$new = $this->restore_backup($backup->provider_backup_id, $old_instance['name'], $old_instance['type']);
		if(!$new['success'])
		{
			$this->die_with_error('Sorry, a problem has occurred while restoring your backup');
		}
		
		$response = $this->ec2->terminate_instances($old_instance['id']);
		$this->test_response($response);
		
		$id = $this->instance->get_instance_ids($old_instance['id']);
		$this->instance->terminate_instance($id[0]['instance_id']);

		return true;
	}

	public function restore_backup_to_new_instance($backup_id, array $settings)
	{
		$name = $settings['name'];
		$type = $settings['type'];
		
		if($type == NULL)
		{
			$type = $this->default_type;
		}
		$this->load->model('Backup_model', 'backup');
		$backup = $this->backup->get_backup_by_id($backup_id);
		
		$this->restore_backup($backup->provider_backup_id, $name, $type);
		return true;
	}

	private function wait_for_backup_to_complete($backup_id)
	{
		$response = $this->ec2->describe_snapshots(array('SnapshotId' => $backup_id));
		if(!$response->isOK()) return false;

		$progress = $response->body->snapshotSet->progress()->map_string();
		$progress = $progress[0];

		if($progress === '100%') return true;

		$this->wait_for_backup_to_complete($backup_id);
	}

	private function wait_for_instance_to_complete($instance_id, $handle = false)
	{
		if(!$handle) $handle = $this->ec2;
		$response = $handle->describe_instances(array('InstanceId' => $instance_id));
		if(!$response->isOK()) return false;

		$state = (string) $response->body->instanceState()->first()->name;

		if($state === 'running') return true;

		$this->wait_for_instance_to_complete($instance_id, $handle);
	}
		
	public function transfer_instances($new_credentials)
	{
		if(empty($new_credentials)) return false;
		$fault = empty($new_credentials['key']) || empty($new_credentials['secret_key']);
		if($fault) return false;

		$new_handle = new AmazonIAM($new_credentials['key'], $new_credentials['secret_key']);
		$response = $new_handle->get_user();
		if(!$response->isOK()) $this->die_with_error("The security credentials you've provided do not seem to be valid.<br />Please try again");

		$new_credentials['user_id'] = (string) $response->body->GetUserResult->User->UserId;

		$new_handle = new AmazonEC2($new_credentials['key'], $new_credentials['secret_key']);

		// write or update new credentials to the database
		$credentials = $this->get_user_aws_credentials();
		if(!$credentials)
		{
			$this->set_user_aws_credentials($new_credentials);
		}
		else if($credentials !== $new_credentials)
		{
			$this->update_user_aws_credentials($new_credentials);
		}

		$success = true;
		// get all of the currently running instances
		$response = $this->ec2->describe_instances(array(
			'Filter' => array(
				array('Name' => 'key-name', 'Value' => $this->username),
				array('Name' => 'instance-state-name', 'Value' => array('running', 'stopped'))
			)
		));
		$this->test_response($response);

		$instances = $response->body->instancesSet();

		// if no instances are there, just return successfully
		if(!$instances) return $success;

		foreach($response->body->instancesSet() as $node)
		{
			$node = $node->item;

			$volume = $node->blockDeviceMapping->volumeId()->map_string();
			$response = $this->ec2->create_snapshot($volume[0], 'to be deleted');
			if(!$response->isOK()) { $success = false; break; }

			$backup_id = $response->body->snapshotId()->map_string();
			$backup_id = $backup_id[0];
			$this->wait_for_backup_to_complete($backup_id);

			$response = $this->ec2->modify_snapshot_attribute($backup_id, 'createVolumePermission', 'add', array('UserId' => $new_credentials['user_id']));
			$this->test_response($response);

			$response = $new_handle->run_instances((string) $node->imageId, 1, 1, array(
				'InstanceType'	=> (string) $node->instanceType,
				'BlockDeviceMapping' => array(
					'DeviceName'				=> '/dev/sda',
					'Ebs.DeleteOnTermination'	=> true,
					'Ebs.SnapshotId'			=> $backup_id
				)
			));
			if(!$response->isOK()) { $success = false; break; }

			$instance_id = $response->body->instanceId()->map_string();
			$instance_id = $instance_id[0];
			
			$name = $this->extract_tag_from_tagset($node->tagSet, 'Name');
			$tag_response = $this->tag_instance($instance_id, "Name", $name);

			$this->wait_for_instance_to_complete($instance_id, $new_handle);

			$this->ec2->delete_snapshot($backup_id);
		}

		return $success;
	}

	private function get_elb_handle()
	{
		$credentials = $this->get_user_aws_credentials();
		if(empty($credentials)) $this->die_with_error('You have to upgrade to TenBrain Premium to use this feature');

		return new AmazonELB($credentials['key'], $credentials['secret_key']);
	}

	public function list_load_balancers($ids)
	{
		$elb = $this->get_elb_handle();
		$response = $elb->describe_load_balancers(array(
			'LoadBalancerNames' => array_keys($ids)
		));

		$balancers = array();
		$list = $response->body->query("descendant-or-self::LoadBalancerName");
		$results = $list->map(function($node){
			return $node->parent();
		});

		$results->each(function($node) use ($ids, &$balancers){
			$name = (string) $node->LoadBalancerName;
			$balancers[] = array(
				'id'		=> $ids[$name],
				'name'		=> $name,
				'provider'	=> 'Amazon',
				'dns_name'	=> (string) $node->DNSName,
				'state'		=> 'On'
				// ''				=> (string) $node->,
			);
		}, $balancers);

		return $balancers;
	}

	public function create_load_balancer($name, array $instances, $gogrid_lb_address = false)
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
		
		$this->load->model('Balancer_model', 'balancer');
		$lb_id = $this->balancer->create_load_balancer($name, $this->name, $name, null);
		
		if($this->register_instances_with_load_balancer($name, array_keys($instances)))
		{
			foreach($instances as $instance)
			{
				$this->balancer->add_load_balancer_instances($lb_id, $instance['instance_id']);
			}
		}

		return true;
	}
	
	public function get_instances_for_lb()
	{
		$account_id = $this->session->userdata('account_id');
		$this->load->model('Balancer_model', 'balancer');
		
		return $this->balancer->get_instances_for_lb($account_id,$this->name);
	}
	
	public function register_instances_within_load_balancer($lb, $instances)
	{
		$this->load->model('Instance_model', 'instances');
		$instance_ids = $this->instances->get_instances_details($instances, array('instance_id', 'provider_instance_id'));
		$instances = array();
		foreach($instance_ids as $inst)
		{
			$instances[$inst->provider_instance_id] = $inst->instance_id;
		}
		
		if($this->register_instances_with_load_balancer($lb->pid, array_keys($instances)))
		{
			foreach(array_values($instances) as $id)
			{
				$this->balancer->add_load_balancer_instances($lb->id, $id);
			}
		}
		return true;
	}

	private function register_instances_with_load_balancer($lb_name, $instances)
	{
		$enabled_zones = $this->get_lb_availability_zones($lb_name);		
		$instances_to_register = array();
		$zones_to_register = array();
		foreach($instances as $instance)
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

	public function delete_load_balancer($id)
	{
		$this->load->model('Balancer_model', 'balancer');
		
		$user_id = $this->session->userdata('account_id');
		
		$name = $this->balancer->get_delete_load_balancer_id($id, $user_id); // should be only one, for amazon unique id is name
		if(!$name) $this->die_with_error('The load balancer you have requested was not found');
		
		$elb = $this->get_elb_handle();
		$response = $elb->delete_load_balancer($name);
		$this->test_response($response);
		
		$this->balancer->delete_load_balancer($id,$user_id);
		
		return true;
	}

	public function show_lb_instances($lb_name)
	{
		$elb = $this->get_elb_handle();
		$response = $elb->describe_load_balancers(
		// array(
			// 'LoadBalancerNames' => $lb_name
		// )
		);
		$instances = array();
		$instances['load_balanced'] = array();
		$response->body->query('descendant-or-self::InstanceId')->each(function($node, $i, &$instances){
			$instances['load_balanced'] []= (string) $node;
		}, $instances);
		
		$response = $this->ec2->describe_instances(array(
			'Filter' => array(
				array('Name' => 'instance-state-name', 'Value' => array('running'))
			)
		));
		$response->body->query('descendant-or-self::instanceId')->each(function($node, $i, &$instances){
			if(in_array((string) $node, $instances['load_balanced'])) return false;
			$instance = $node->parent();
			$instances []= array(
				'id'				=> $i,
				'name'				=> (string) $instance->tagSet->query("descendant-or-self::item[key='Name']/value")->first(),
				'instance_id'		=> (string) $instance->instanceId,
				'ip_address'		=> (string) $instance->ipAddress,
				// ''				=> (string) $instance->,
			);
		}, $instances);
		unset($instances['load_balanced']);

		return $instances;
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

	public function deregister_instances_from_lb($lb, $instances)
	{
		$this->load->model('Instance_model', 'instances');
		$instance_ids = $this->instances->get_instances_details($instances, array('provider_instance_id', 'instance_id'));
		$instances = array();
		foreach($instance_ids as $inst)
		{
			$instances[$inst->provider_instance_id] = $inst->instance_id;
		}
		
		if($this->deregister_instances_from_load_balancer($lb->pid, array_keys($instances)))
		{
			foreach(array_values($instances) as $id)
			{
				$this->balancer->deregister_instance_from_lb($id, $lb->id);
			}
		}
		return true;
	}

	private function deregister_instances_from_load_balancer($lb_name, $instances)
	{
		$instances_to_deregister = array();
		foreach($instances as $instance)
		{
			$instances_to_deregister []= array('InstanceId' => $instance);
		}
		$elb = $this->get_elb_handle();
		$response = $elb->deregister_instances_from_load_balancer($lb_name, $instances_to_deregister);
		$this->test_response($response);
		return true;
	}
	
	public function get_load_balanced_instances($lb_pid, $lb_dbid)
	{
		$lb_name = $lb_pid;	// for amazon name is a unique lb identifier
		if(!$lb_name) return array();
		
		$elb = $this->get_elb_handle();	
		$response = $elb->describe_instance_health($lb_name);
		$list = $response->body->query('descendant-or-self::InstanceId');		
		$results = $list->map(function($node){
			return $node->parent();
		});
		
		$instances = array();
		$results->each(function($node) use (&$instances){
			$instances[(string) $node->InstanceId] = array(
				'healthy'			=> (string) $node->State === 'InService',
				'health_message'	=> (string) $node->Description
			);
		}, $instances);
		
		$db_instances = $this->balancer->get_load_balanced_instances($lb_dbid);		
		$out = array();
		foreach($instances as $id => $instance)
		{
			$out []= array_merge($instance, $db_instances[$id]);
		}
		return $out;
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

	public function test()
	{
		$output = $this->get_available_server_types();
		print_r($output);
		echo PHP_EOL;die;
	}
}