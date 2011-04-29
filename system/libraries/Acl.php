<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Acl
{
	// Set the instance variable
	var $CI;

	function __construct()
	{
		// Get the instance
		$this->CI =& get_instance();

		// Set the include path and require the needed files
		set_include_path(get_include_path() . PATH_SEPARATOR . BASEPATH . "application/libraries");
		
		require_once(APPPATH . '/libraries/Zend/Acl.php');
		require_once(APPPATH . '/libraries/Zend/Acl/Role.php');
		require_once(APPPATH . '/libraries/Zend/Acl/Resource.php');
		
		$this->zacl = new Zend_Acl();

		// Set the default ACL
		$this->zacl->addRole(new Zend_Acl_Role('default'));
		$query = $this->CI->db->get('tbl_aclresources');
		foreach($query->result() AS $row)
		{
			$this->zacl->add(new Zend_Acl_Resource($row->resource));
			if($row->default_value == 'true')
			{
				$this->zacl->allow('default', $row->resource);
			}
		}
		// Get the ACL for the roles
		$this->CI->db->order_by("roleorder", "ASC");
		$query = $this->CI->db->get('tbl_aclroles');
		foreach($query->result() AS $row)
		{
			$role = (string)$row->name;
			$this->zacl->addRole(new Zend_Acl_Role($role), 'default');
			$this->CI->db->from('tbl_acl');
			$this->CI->db->join('tbl_aclresources', 'tbl_acl.resource_id = tbl_aclresources.id');
			$this->CI->db->where('type', 'role');
			$this->CI->db->where('type_id', $row->id);
			
			$subquery = $this->CI->db->get();
			foreach($subquery->result() AS $subrow)
			{
				if($subrow->action == "allow")
				{
					$this->zacl->allow($role, $subrow->resource);
				}
				else
				{
					$this->zacl->deny($role, $subrow->resource);
				}
			}
			
			// Get the ACL for the users
			$this->CI->db->from('tbl_users');
			$this->CI->db->where('roleid', $row->id);
			$userquery = $this->CI->db->get();
			
			foreach($userquery->result() AS $userrow)
			{
				$this->zacl->addRole(new Zend_Acl_Role($userrow->user), $role);
				$this->CI->db->from('tbl_acl');
				$this->CI->db->join('tbl_aclresources', 'tbl_acl.resource_id = tbl_aclresources.id');
				$this->CI->db->where('type', 'user');
				$this->CI->db->where('type_id', $userrow->userid);
				$usersubquery = $this->CI->db->get();
				foreach($usersubquery->result() AS $usersubrow)
				{
					if($usersubrow->action == "allow")
					{
						$this->zacl->allow($userrow->user, $usersubrow->resource);
					}
					else
					{
						$this->zacl->deny($userrow->user, $usersubrow->resource);
					}
				}
			}
		}
	}

	// Function to check if the current or a preset role has access to a resource
	function check_acl($resource, $role = '')
	{
		if(!$this->zacl->has($resource))
		{
			return 1;
		}
		if(empty($role))
		{
			if(isset($this->CI->session->userdata['user']))
			{
				$role = $this->CI->session->userdata['user'];
			}
		}
		if(empty($role))
		{
			return false;
		}
		return $this->zacl->isAllowed($role, $resource);
	}
}