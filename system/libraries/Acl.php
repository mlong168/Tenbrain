<?php if(!defined('BASEPATH')) exit('No direct script access allowed');

class Acl
{
	// Set the instance variable
	private $CI;
	private $role = 0;

	function __construct()
	{
		// Get the instance
		$this->CI =& get_instance();

		// Set the include path and require the needed files
		set_include_path(get_include_path() . PATH_SEPARATOR . BASEPATH . 'application/libraries');
		
		require_once(APPPATH . '/libraries/Zend/Acl.php');
		require_once(APPPATH . '/libraries/Zend/Acl/Role.php');
		require_once(APPPATH . '/libraries/Zend/Acl/Resource.php');
		
		// get the role for current user
		$this->CI->db->select('roleid');
		$query = $this->CI->db->get_where('a3m_account', array('id' => $this->CI->session->userdata('account_id')));
		if($query->num_rows())
		{
			$this->role = $query->row()->roleid;
		}

		$this->acl = new Zend_Acl();
 
		$this->CI->db->order_by('ParentId', 'ASC'); //Get the roles
		$query = $this->CI->db->get('user_roles');
		$roles = $query->result();
 
		$this->CI->db->order_by('parentId', 'ASC'); //Get the resources
		$query = $this->CI->db->get('user_resources');
		$resources = $query->result();
 
		$query = $this->CI->db->get('user_permissions'); //Get the permissions
		$permissions = $query->result();

		//Add the roles to the ACL
		foreach ($roles as $roles)
		{
			$role = new Zend_Acl_Role($roles->id);
			$roles->parentId != null ?
				$this->acl->addRole($role,$roles->parentId): 
				$this->acl->addRole($role);
		}

		//Add the resources to the ACL
		foreach($resources as $resources)
		{
			$resource = new Zend_Acl_Resource($resources->id);
			$resources->parentId != null ?
				$this->acl->add($resource, $resources->parentId):
				$this->acl->add($resource);
		}

		//Add the permissions to the ACL
		foreach($permissions as $perms)
		{
			$perms->read == '1' ? 
				$this->acl->allow($perms->role, $perms->resource, 'read') : 
				$this->acl->deny($perms->role, $perms->resource, 'read');
			$perms->write == '1' ? 
				$this->acl->allow($perms->role, $perms->resource, 'write') : 
				$this->acl->deny($perms->role, $perms->resource, 'write');
			$perms->modify == '1' ? 
				$this->acl->allow($perms->role, $perms->resource, 'modify') : 
				$this->acl->deny($perms->role, $perms->resource, 'modify');
			$perms->publish == '1' ? 
				$this->acl->allow($perms->role, $perms->resource, 'publish') : 
				$this->acl->deny($perms->role, $perms->resource, 'publish');
			$perms->delete == '1' ? 
				$this->acl->allow($perms->role, $perms->resource, 'delete') : 
				$this->acl->deny($perms->role, $perms->resource, 'delete');
		}
	}

	// Function to check if the current or a preset role has access to a resource
	function check_acl($resource, $role = '')
	{
		if(!$this->acl->has($resource))
		{
			return true;
		}
		if(empty($role))
		{
			if(isset($this->role))
			{
				$role = $this->role;
			}
		}
		if(empty($role))
		{
			return false;
		}
		return $this->zacl->isAllowed($role, $resource);
	}
	
	function can_read($role, $resource)
	{
		return $this->acl->isAllowed($role, $resource, 'read')? TRUE : FALSE;
	}
	
	function can_write($role, $resource)
	{
		return $this->acl->isAllowed($role, $resource, 'write')? TRUE : FALSE;
	}
	
	function can_modify($role, $resource)
	{
		return $this->acl->isAllowed($role, $resource, 'modify')? TRUE : FALSE;
	}
	
	function can_delete($role, $resource)
	{
		return $this->acl->isAllowed($role, $resource, 'delete')? TRUE : FALSE;
	}
	
	function can_publish($role, $resource)
	{
		return $this->acl->isAllowed($role, $resource, 'publish')? TRUE : FALSE;
	}
}