<?php

class Amazon_model extends Model {
	
	private $ec2;
	private $username;

	function __construct()
	{
		parent::Model();
		
		$this->load->helper('amazon_sdk/sdk');
		
		$this->ec2 = new AmazonEC2();
		$this->username = $this->account_model->get_by_id($this->session->userdata('account_id'))->username;
	}
	
	function describe_instances($state)
	{
		$states_filter = array();
		if($state === 'running')
		{
			$states_filter['Name'] = 'instance-state-name';
			$states_filter['Value'] = array('running', 'pending', 'shutting-down', 'stopping');
		}
		else
		{
			$states_filter []= array('Name' => 'instance-state-name', 'Value' => $state);
		}
		
		$response = $this->ec2->describe_instances(array(
			'Filter' => array(
				$states_filter,
				array('Name' => 'key-name', 'Value' => $this->username),
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
			'success'	=> $ok,
			'instances'	=> $instances
		);
	}
	
	function describe_images()
	{
		$response = $this->ec2->describe_images(array(
			'Filter' => array(
				array('Name' => 'name',		'Value' => 'Cvoice'	 )
				// array('Name' => 'architecture',		'Value' => 'x86_64'	 ),
				// array('Name' => 'image-type',		  'Value' => 'machine'	),
				// array('Name' => 'root-device-type',	'Value' => 'ebs'		),
				// array('Name' => 'virtualization-type', 'Value' => 'paravirtual'),
			)
		));
		
		$images = array();
		$ok = $response->isOK();
		if($ok)
		{
			$list = $response->body->imageId();

			$results = $list->map(function($node)
			{
				return $node->parent();
			});

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
		}
		
		return array(
			'success'	=> $ok,
			'images'	=> $images
		);
	}
	
	function launch_instance($image_id, $type, $name)
	{
		$username = $this->username;		
		$response = $this->ec2->describe_key_pairs(array(
			'KeyName' => $username
		));
		if(!$response->isOK())
		{
			$new_key = $this->ec2->create_key_pair($username);
		}
		
		$response = $this->ec2->run_instances($image_id, 1, 1, array(
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
			
			$tag_response = $this->ec2->create_tags($instance_id, array(
				array('Key' => 'Name', 'Value' => $name)
			));
		}
		
		return $launch_status;
	}

	function terminate_instance($instance_id)
	{
		$response = $this->ec2->terminate_instances($instance_id);

		return $response->isOK();
	}

	function start_instance($instance_id)
	{
		$response = $this->ec2->start_instances($instance_id);

		return $response->isOK();
	}

	function stop_instance($instance_id)
	{
		$response = $this->ec2->stop_instances($instance_id);

		return $response->isOK();
	}

	function reboot_instance($instance_id)
	{
		$response = $this->ec2->reboot_instances($instance_id);

		return $response->isOK();
	}
	
	function get_instance_volume($instance_id)
	{
		$response = $this->ec2->describe_instance_attribute($instance_id, 'blockDeviceMapping');
		$ok = $response->isOK();
		$volume_id = '';
		if($ok)
		{
			$volume_ids = $response->body->volumeId();
			$volume_id = (string) $volume_ids[0];
		}
		return $volume_id;
	}
	
	function created_snapshots($instance_id = false)
	{
		$filter = array(
			array('Name' => 'tag:User', 'Value' => $this->username)
		);
		if($instance_id)
		{
			$volume_id = $this->get_instance_volume($instance_id);
			if(!empty($volume_id))
			{
				$filter []= array('Name' => 'volume-id', 'Value' => $volume_id);
			}
			else
			{
				// fucked up getting the volume id
			}
		}
		$response = $this->ec2->describe_snapshots(array(
			'Owner'		=> 'self',
			'Filter'	=> $filter
		));
		
		$snapshots = array();
		$ok = $response->isOK();
		
		if($ok)
		{
			$list = $response->body->snapshotSet();			
			if($list->count())
			{
				$list->each(function($node, $i, &$snapshots)
				{
					$node = $node->item;
					$tags = $node->tagSet;
					$name = '<i>not set</i>';
					if($tags->count())
					{
						$name_ary = $tags->xpath("item[key='Name']/value");
						$name = (string) $name_ary[0];
					}
					$time = (string) $node->startTime;
					$time = date('Y-m-d H:i', strtotime($time));
					$snapshots[] = array(
						'id'				=> $i,
						'name'				=> $name,
						'snapshot_id'		=> (string) $node->snapshotId,
						'capacity'			=> (string) $node->volumeSize . 'GB',
						'description'		=> (string) $node->description,
						'status'			=> (string) $node->status,
						'progress'			=> (string) $node->progress,
						'started'			=> $time
						// ''				=> (string) $node->,
					);

				}, $snapshots);
			}
		}
		
		return array(
			'success'	=> $ok,
			'snapshots'	=> $snapshots
		);		
	}
	
	function create_snapshot($instance_id, $name, $description = 'sample description')
	{
		$response = $this->ec2->describe_volumes(array(
			'Filter' => array(
				array('Name' => 'attachment.instance-id', 'Value' => $instance_id)
			)
		));
		
		$ok = $response->isOK();
		if($ok)
		{
			$volume = $response->body->volumeId()->map_string();
			$snap = $this->ec2->create_snapshot($volume[0], $description);
			
			$ok = $snap->isOK();
			if($ok)
			{
				$snap_id = $snap->body->snapshotId()->map_string();
				$snap_id = $snap_id[0];
			
				$tag_response = $this->ec2->create_tags($snap_id, array(
					array('Key' => 'Name', 'Value' => $name),
					array('Key' => 'User', 'Value' => $this->username)
				));
			}
		}
		
		return $ok;
	}
	
	function test()
	{
		// $response = $this->ec2->describe_volumes(array(
			// 'VolumeId' => 'vol-54d1bb3c'
		// ));
		// $response = $this->ec2->describe_instances(array(
			// 'Filter' => array(
				// array('Name' => 'key-name', 'Value' => 'slavko'),
			// )
		// ));
		// $response = $this->ec2->run_instances('ami-6e7c8d07', 1, 1, array(
			// 'KeyName'		=> 'tenbrain',
			// 'InstanceType'	=> 't1.micro',
			
			// 'BlockDeviceMapping' => array(
				// 'VirtualName'				=> 'slavko',
				// 'DeviceName'				=> '/dev/sda',
				// 'Ebs.DeleteOnTermination'	=> true
			// )
			
		// ));
		
		// var_dump($response->isOK());
		echo PHP_EOL;
		// print_r($response->body);
	}
}