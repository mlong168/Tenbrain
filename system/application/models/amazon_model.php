<?php

class Amazon_model extends Model {
	
	private $ec2;
	private $username;

	function __construct()
	{
		parent::Model();
		
		$this->load->helper('amazon_sdk/sdk');
		
		$this->ec2 = new AmazonEC2();
		$this->username = $this->authentication->is_signed_in()
			? $this->account_model->get_by_id($this->session->userdata('account_id'))->username
			: 'anonymous';
	}
	
	private function die_with_error($error_message)
	{
		header('Content-type: application/json');
		echo json_encode(array(
			'success'		=> false,
			'error_message'	=> $error_message
		));
		die;
	}
	
	private function test_response($response)
	{
		if(!$response->isOK())
		{
			$error = $response->body->Error();
			$this->die_with_error((string) $error[0]->Message);
		}
	}
	
	private function extract_name_from_tagset($tagset)
	{
		$name = '';
		foreach($tagset as $item)
		{
			$item = $item->item;
			if((string) $item->key === 'Name')
			{
				$name = (string) $item->value;
				break;
			}
			
		}
		return $name;
	}
	
	public function describe_instances($state)
	{
		$states_filter = array();
		if($state === 'running')
		{
			$states_filter['Name'] = 'instance-state-name';
			$states_filter['Value'] = array('running', 'pending', 'shutting-down', 'stopping');
		}
		else if($state === 'stopped')
		{
			$states_filter['Name'] = 'instance-state-name';
			$states_filter['Value'] = array('stopped', 'pending', 'shutting-down');			
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
		
		$this->test_response($response);

		$instances = array();
		if(count($response->body->item()) > 1)
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
			'success'	=> true,
			'instances'	=> $instances
		);
	}
	
	public function describe_images()
	{
		$response = $this->ec2->describe_images(array('Owner' => '528233608018'));
		
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
	
	public function launch_instance($image_id, $type, $name)
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
				'DeviceName'				=> '/dev/sda1',
				'Ebs.DeleteOnTermination'	=> true
			)			
		));
		
		$this->test_response($response);
		
		$instance_id = $response->body->instanceId();
		$instance_id = (string) $instance_id[0];
		
		$tag_response = $this->ec2->create_tags($instance_id, array(
			array('Key' => 'Name', 'Value' => $name)
		));
		
		return true;
	}

	public function terminate_instance($instance_id)
	{
		$response = $this->ec2->terminate_instances($instance_id);
		$this->test_response($response);

		return true;
	}

	public function start_instance($instance_id)
	{
		$response = $this->ec2->start_instances($instance_id);
		$this->test_response($response);

		return true;
	}

	public function stop_instance($instance_id)
	{
		$response = $this->ec2->stop_instances($instance_id);
		$this->test_response($response);

		return true;
	}

	public function reboot_instance($instance_id)
	{
		$response = $this->ec2->reboot_instances($instance_id);
		$this->test_response($response);

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
	
	public function created_snapshots($instance_id = false)
	{
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
		$snapshots = array();
		$i = 0;
		foreach($response->body->snapshotSet->item as $node)
		{
			$time = (string) $node->startTime;
			$time = date('Y-m-d H:i', strtotime($time));
			$snapshots[] = array(
				'id'				=> $i,
				'name'				=> $this->extract_name_from_tagset($node->tagSet),
				'snapshot_id'		=> (string) $node->snapshotId,
				'capacity'			=> (string) $node->volumeSize . 'GB',
				'description'		=> (string) $node->description,
				'status'			=> (string) $node->status,
				'progress'			=> (string) $node->progress,
				'started'			=> $time
				// ''				=> (string) $node->,
			);
			$i++;
		}
		
		return array(
			'success'	=> true,
			'snapshots'	=> $snapshots
		);		
	}
	
	private function get_snapshot_volume($snapshot_id = false)
	{
		if(!$snapshot_id) $this->die_with_error('No snapshot specified');
		
		$response = $this->ec2->describe_snapshots(array('SnapshotId' => $snapshot_id));
		$this->test_response($response);
		
		return (string) $response->body->volumeId()->first();
	}
	
	private function get_snapshot_instance($snapshot_id = false, $describe = false)
	{
		$response = $this->ec2->describe_instances(array(
			'Filter' => array(
				array('Name' => 'block-device-mapping.volume-id', 'Value' => $this->get_snapshot_volume($snapshot_id)),
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
					'ip_address'		=> (string) $node->ipAddress,
					'image_id'			=> (string) $node->imageId,
					'state'				=> (string) $node->instanceState->name,
					'virtualization'	=> (string) $node->virtualizationType,
					'type'				=> (string) $node->instanceType,
					'root_device'		=> (string) $node->rootDeviceType
					// ''				=> (string) $node->,
				);
			}, $instances);
		}
		
		return $describe ? $instances : $instances[0]['instance_id'];
	}
	
	public function describe_snapshot_instance($snapshot_id = false)
	{
		if(!$snapshot_id) $this->die_with_error('No snapshot specified');
		
		$instances = $this->get_snapshot_instance($snapshot_id, true);
		return array(
			'success'	=> true,
			'instances'	=> $instances
		);
	}
	
	public function create_snapshot($instance_id, $name, $description = 'sample description')
	{
		$response = $this->ec2->describe_volumes(array(
			'Filter' => array(
				array('Name' => 'attachment.instance-id', 'Value' => $instance_id)
			)
		));
		$this->test_response($response);		
		if(!$response->body->volumeSet()->first()->count())
		{
			$this->die_with_error('The snapshot could not be created from an instance yet');
		}
		
		$volume_id = $response->body->volumeId()->map_string();
		$response = $this->ec2->create_snapshot($volume_id[0], $description);
		$this->test_response($response);
		
		$snap_id = $response->body->snapshotId()->map_string();
		$snap_id = $snap_id[0];
	
		$tag_response = $this->ec2->create_tags($snap_id, array(
			array('Key' => 'Name', 'Value' => $name),
			array('Key' => 'User', 'Value' => $this->username)
		));
		
		return true;
	}
	
	public function delete_snapshot($snapshot_id = false)
	{
		if(!$snapshot_id) $this->die_with_error('No snapshot specified');
		
		$response = $this->ec2->delete_snapshot($snapshot_id);
		$this->test_response($response);
		
		return true;
	}
	
	/*
	 * restores snapshot to new instance
	 * returns new instance's parameners along with the old one's
	 */
	private function restore_snapshot($snapshot_id, $name = '')
	{	
		$response = $this->ec2->describe_instances(array(
			'Filter' => array(
				array('Name' => 'block-device-mapping.volume-id', 'Value' => $this->get_snapshot_volume($snapshot_id))
			)
		));
		$this->test_response($response);
		
		$old_instance = $response->body->instancesSet();
		if(!$old_instance) $this->die_with_error('The instance this snapshot was created off has been terminated');
		
		$old_instance = $old_instance->first()->item;
		
		$old_instance = array(
			'id'		=> (string) $old_instance->instanceId,
			'image_id'	=> (string) $old_instance->imageId,
			'key_name'	=> (string) $old_instance->keyName,
			'type'		=> (string) $old_instance->instanceType,
			'volume_id'	=> (string) $old_instance->blockDeviceMapping->ebs()->first()->volumeId,
			'name'		=> $this->extract_name_from_tagset($old_instance->tagSet)
			// ''	=> (string) $old_instance->
		);
		
		$response = $this->ec2->run_instances($old_instance['image_id'], 1, 1, array(
			'KeyName'		=> $old_instance['key_name'],
			'InstanceType'	=> $old_instance['type'],
			
			'BlockDeviceMapping' => array(
				'DeviceName'				=> '/dev/sda',
				'Ebs.DeleteOnTermination'	=> true,
				'Ebs.SnapshotId'			=> $snapshot_id
			)
		));
		$this->test_response($response);
		
		$new_instance_id = $response->body->instanceId()->map_string();
		$new_instance_id = $new_instance_id[0];
		
		$tag_response = $this->ec2->create_tags($new_instance_id, array(
			array('Key' => 'Name', 'Value' => empty($name) ? $old_instance['name'] : $name)
		));
		
		return array(
			'success'			=> true,
			'old_instance'		=> $old_instance,
			'new_instance_id'	=> $new_instance_id
		);
	}
	
	public function restore_snapshot_to_corresponding_instance($snapshot_id = false)
	{
		$new = $this->restore_snapshot($snapshot_id);
		if(!$new['success'])
		{
			$this->die_with_error('Sorry, a problem has occurred while restoring your snapshot');
		}
		
		$response = $this->ec2->describe_snapshots(array(
			'Filter' => array(
				array('Name' => 'volume-id', 'Value' => $new['old_instance']['volume_id'])
			)
		));
		$this->test_response($response);
		
		foreach($response->body->snapshotSet->item as $snap)
		{
			$snap_id = (string) $snap->snapshotId;
			if($snap_id !== $snapshot_id) $this->ec2->delete_snapshot($snap_id);
		}
		
		$this->terminate_instance($new['old_instance']['id']);
		
		// $name = $this->extract_name_from_tagset($response->body->snapshotSet()->first()->item->tagSet);
		// $description = (string) $set->description;		
		// $this->create_snapshot($new['new_instance_id'], $name, $description);
		
		return true;
	}
	
	public function restore_snapshot_to_new_instance($snapshot_id, $name)
	{
		$this->restore_snapshot($snapshot_id, $name);
		return true;		
	}
	
	public function test()
	{		
		// var_dump($response->isOK());
		echo PHP_EOL;
		// print_r($response->body);
	}
}