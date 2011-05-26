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
	
	public function launch_server(array $params)
	{
		
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