<?php

require_once APPLICATION_PATH . '/../library/Amazon/sdk.class.php';

class Application_Model_Provider_Amazon extends Application_Model_Provider
{

	const TENBRAIN_API_KEY = 'AKIAJHXE62BKBM2RJWBA';
	const TENBRAIN_API_SECRET = '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';
	const TENBRAIN_API_USER_ID = '528233608018';

	public $name = 'Amazon';
	
	private $ec2;
	private $cassie;
	
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
		
		$this->cassie = new Application_Model_Servers();
			
		$this->ec2 = new AmazonEC2(self::TENBRAIN_API_KEY, self::TENBRAIN_API_SECRET);
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
		$username = $auth->getIdentity()->username;
		
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
		// $this->db->set('account_id', $this->session->userdata('account_id'));
		// $this->db->set('key_pair_name', $this->username);
		// $this->db->set('private_key', $private_key);
		// $this->db->insert('account_key_pairs');
		
		return true;
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

		$instance_id = $response->body->instanceId();
		$instance_id = (string) $instance_id[0];
		
		$this->tag_instance($instance_id, 'Name', $name);
		
		// write to db if things went fine
		// $this->instance->add_user_instance(array(
			// 'account_id' => $this->session->userdata('account_id'),
			// 'provider_instance_id' => $instance_id,
			// 'instance_name' => $name,
			// 'provider' => 'Amazon'
		// ));
		return true;
	}
	
	public function list_servers($ids = array())
	{
		// print_r($this->cassie->get($ids));
		$input_ary = array();	// temporary, to be extracted from db
		$response = $this->ec2->describe_instances(array(
			'InstanceId' => array_keys($input_ary)
		));
		// $this->test_response($response);

		$instances = array();
		$list = $response->body->query("descendant-or-self::instanceId")->map(function($node){
			return $node->parent();
		})->each(function($node) use(&$instances, $input_ary){
			$name = $node->tagSet->xpath("descendant-or-self::item[key='Name']/value");
			$name = $name ? (string) $name[0] : '<i>not set</i>';
			$id = (string) $node->instanceId;
			$instances[] = array(
				// 'id'				=> $input_ary[$id],
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

	public function start_servers(array $ids)
	{
		
	}
	
	public function stop_servers(array $ids)
	{
		
	}
	
	public function reboot_servers(array $ids)
	{
		
	}
	
	public function terminate_servers(array $ids)
	{
		
	}
	
	public function create_load_balancer($name, array $instances, $gogrid_lb_address)
	{
		
	}
	
	public function delete_load_balancer($id)
	{
		
	}
}