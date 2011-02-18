<?php

class Amazon extends Controller {

	function __construct()
	{
		parent::Controller();
		
		// amazon sdk:
		$this->load->helper('amazon_sdk/sdk');
		
		//authentication stuff:		
		$this->load->helper(array('language'));
        $this->load->library(array('account/authentication'));
		$this->load->model(array('account/account_model'));
		$this->lang->load(array('general'));
		
		if(!$this->authentication->is_signed_in())
		{
			echo json_encode(array(
				'success'	=> false,
				'message'	=> 'you do not have the permission to access this page'
			));
			die();
		}
	}

	function index()
	{
		header('Content-type:text/plain');

		$ec2 = new AmazonEC2();
		
		
		// $response = $ec2->describe_volumes(array(
			// 'VolumeId' => 'vol-54d1bb3c'
		// ));
		// $response = $ec2->describe_instances(array(
			// 'Filter' => array(
				// array('Name' => 'key-name', 'Value' => 'slavko'),
			// )
		// ));
		// $response = $ec2->run_instances('ami-6e7c8d07', 1, 1, array(
			// 'KeyName'		=> 'tenbrain',
			// 'InstanceType'	=> 't1.micro',
			
			// 'BlockDeviceMapping' => array(
				// 'VirtualName'				=> 'slavko',
				// 'DeviceName'				=> '/dev/sda',
				// 'Ebs.DeleteOnTermination'	=> true
			// )
			
		// ));
		
		var_dump($response->isOK());
		echo PHP_EOL;
		print_r($response->body);
		die(PHP_EOL . 'voila! this is an amazon controller index function');
	}

	function show_instances($state)
	{
		header('Content-type:application/json');
		error_reporting(E_ALL ^ E_NOTICE);
		
		$states = array('pending', 'running', 'shutting-down', 'terminated', 'stopping', 'stopped');
		
		if(empty($state) || !in_array($state, $states))
		{
			$state = 'running';
		}
		
		$ec2 = new AmazonEC2();
		$response = $ec2->describe_instances(array(
			'Filter' => array(
				array('Name' => 'instance-state-name', 'Value' => $state),
				array('Name' => 'key-name', 'Value' => $this->account_model->get_by_id($this->session->userdata('account_id'))->username),
			)
		));

		$instances = array();
		$ok = $response->isOK();
		if($ok && count($response->body->item()) > 1)
		{
			$list = $response->body->instanceId();

			$results = $list->map(function($node)
			{
				return $node->parent();
			});

			$results->each(function($node, $i, &$instances){
				$instances[] = array(
					'id'				=> $i,
					'name'				=> (string) $node->tagSet->item->value,
					'dns_name'			=> (string) $node->dnsName,
					'ip_address'		=> (string) $node->ipAddress,
					'instance_id'		=> (string) $node->instanceId,
					'image_id'			=> (string) $node->imageId,
					'state'				=> (string) $node->instanceState->name,
					'virtualization'	=> (string) $node->virtualizationType,
					'type'				=> (string) $node->instanceType,
					'root_device'		=> (string) $node->rootDeviceType
					// ''				=> (string) $node->,
				);
			}, $instances);
		}

		echo json_encode(array(
			'success'	=> $ok,
			'instances'	=> $instances
		));
	}

	function available_images()
	{
		$ec2 = new AmazonEC2();
		$response = $ec2->describe_images(array(
			'Filter' => array(
				array('Name' => 'name',		'Value' => 'Cvoice'	 )
				// array('Name' => 'architecture',		'Value' => 'x86_64'	 ),
				// array('Name' => 'image-type',		  'Value' => 'machine'	),
				// array('Name' => 'root-device-type',	'Value' => 'ebs'		),
				// array('Name' => 'virtualization-type', 'Value' => 'paravirtual'),
			)
		));

		$list = $response->body->imageId();

		$results = $list->map(function($node)
		{
			return $node->parent();
		});

		$images = array();
		$results->each(function($node, $i, &$images)
		{
			$images[] = array(
				'id'				=> $i,
				'image_id'			=> (string) $node->imageId,
				'name'				=> (string) $node->name,
				'state'				=> (string) $node->imageState,
				'description'		=> (string) $node->description,
				'virtualization'	=> (string) $node->virtualizationType,
				'location'			=> (string) $node->imageLocation
				// ''				=> (string) $node->,
			);

		}, $images);


		header('Content-type:application/json');
		echo json_encode(array(
			'success'	=> true,
			'images'	=> $images
		));
	}

	function launch_instance()
	{
		$available_types = array('t1.micro', 'm1.small', 'm1.large', 'm1.xlarge', 'm2.xlarge', 'm2.2xlarge', 'm2.4xlarge', 'c1.medium', 'c1.xlarge', 'cc1.4xlarge', 'cg1.4xlarge');
		$type = $this->input->post('instance_type');
		if(!in_array($type, $available_types))
		{
			$type = 't1.micro';
		}
		
		$ec2 = new AmazonEC2();
		
		$username = $this->account_model->get_by_id($this->session->userdata('account_id'))->username;		
		$response = $ec2->describe_key_pairs(array(
			'KeyName' => $username
		));
		if(!$response->isOK())
		{
			$new_key = $ec2->create_key_pair($username);
		}
		
		$response = $ec2->run_instances($this->input->post('image_id'), 1, 1, array(
			'KeyName'		=> $username,
			'InstanceType'	=> $type,
			
			'BlockDeviceMapping' => array(
				'DeviceName'				=> '/dev/sda',
				'Ebs.DeleteOnTermination'	=> true
			)
			
		));
		
		$launch_status = $response->isOK();
		if($launch_status)
		{
			$instance_id = $response->body->instanceId();
			$instance_id = (string) $instance_id[0];
			
			$tag_response = $ec2->create_tags($instance_id, array(
				array('Key' => 'Name', 'Value' => $this->input->post('instance_name'))
			));
		}

		header('Content-type:application/json');
		echo json_encode(array(
			'success'	=> $response->isOK()
		));
	}

	function terminate_instance()
	{
		$ec2 = new AmazonEC2();
		$response = $ec2->terminate_instances($this->input->post('instance_id'));

		header('Content-type:application/json');
		echo json_encode(array(
			'success'	=> $response->isOK()
		));
	}

	function start_instance()
	{
		$ec2 = new AmazonEC2();
		$response = $ec2->start_instances($this->input->post('instance_id'));

		header('Content-type:application/json');
		echo json_encode(array(
			'success'	=> $response->isOK()
		));
	}

	function stop_instance()
	{
		$ec2 = new AmazonEC2();
		$response = $ec2->stop_instances($this->input->post('instance_id'));

		header('Content-type:application/json');
		echo json_encode(array(
			'success'	=> $response->isOK()
		));
	}

	function reboot_instance()
	{
		$ec2 = new AmazonEC2();
		$response = $ec2->reboot_instances($this->input->post('instance_id'));

		header('Content-type:application/json');
		echo json_encode(array(
			'success'	=> $response->isOK()
		));
	}
}

/* End of file amazon.php */
/* Location: ./system/application/controllers/amazon.php */