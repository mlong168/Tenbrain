<?php
class ZendExt_ACL extends Zend_Acl
{
    private $_db; 
 
    public $_getUserRoleName = null; 
 
    public $_getUserRoleId = null; 
 
    public $_user = null; 
 
    public function __construct($user) 
    { 
        $this->_user = $user->username; 
        
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $resources = $bootstrap->getOption('resources');
        $db = $resources['db'];
        
        $this->_db = Zend_Db::factory($db['adapter'], $db['params']);
        
        self::roleResource(); 
 
        $getUserRole = $this->_db->fetchRow( 
        $this->_db->select() 
            ->from('acl_roles') 
            ->from('accounts') 
            ->where('accounts.username = "' . $this->_user . '"') 
            ->where('accounts.role_id = acl_roles.role_id'));

        $this->_getUserRoleId = $getUserRole['role_id'] ? $getUserRole['role_id'] : 4; 
        $this->_getUserRoleName = $getUserRole['role_name'] ? $getUserRole['role_name'] : 'Guest';
        
        $this->addRole(new Zend_Acl_Role($this->_user), $this->_getUserRoleName); 
 
    } 
 
    private function initRoles() 
    { 
        $roles = $this->_db->fetchAll( 
        $this->_db->select() 
            ->from('acl_roles') 
            ->order(array('role_id DESC'))); 
 
        $this->addRole(new Zend_Acl_Role($roles[0]['role_name'])); 
 
        for ($i = 1; $i < count($roles); $i++) { 
            $this->addRole(new Zend_Acl_Role($roles[$i]['role_name']), $roles[$i-1]['role_name']); 
        } 
    } 
 
    private function initResources() 
    { 
        self::initRoles(); 
 
        $resources = $this->_db->fetchAll( 
        $this->_db->select() 
            ->from('acl_resources')); 
 
        foreach ($resources as $key=>$value){ 
            if (!$this->has($value['resource'])) { 
                $this->add(new Zend_Acl_Resource($value['resource'])); 
            } 
        } 
    } 
 
    private function roleResource() 
    { 
        self::initResources(); 
 
        $acl = $this->_db->fetchAll( 
        $this->_db->select() 
            ->from('acl_roles') 
            ->from('acl_resources') 
            ->from('acl_permissions') 
            ->where('acl_roles.role_id = acl_permissions.role_id')); 
 
        foreach ($acl as $key=>$value) { 
            $this->allow($value['role_name'], $value['resource'],$value['permission']); 
        } 
    } 
 
    public function listRoles() 
    { 
        return $this->_db->fetchAll( 
        $this->_db->select() 
            ->from('acl_roles')); 
    } 
 
    public function getRoleId($roleName) 
    { 
        return $this->_db->fetchRow( 
        $this->_db->select() 
            ->from('acl_roles', 'role_id') 
            ->where('acl_roles.role_name = "' . $roleName . '"')); 
    } 
 
    public function insertAclUser() 
    { 
        $data = array( 
            'role_id' => $this->_getUserRoleId, 
            'username' => $this->_user); 
 
        return $this->_db->insert('accounts',$data); 
    } 
 
    public function listResources() 
    { 
        return $this->_db->fetchAll( 
        $this->_db->select() 
            ->from('acl_resources') 
            ->from('acl_permissions') 
            ->where('resource_uid = uid')); 
    } 
 
    public function listResourcesByGroup($group) 
    { 
        $result = null; 
        $group = $this->_db->fetchAll($this->_db->select() 
            ->from('acl_resources') 
            ->from('acl_permissions') 
            ->where('acl_resources.resource = "' . $group . '"') 
            ->where('uid = resource_uid') 
        ); 
        
        foreach ($group as $key=>$value) { 
            if($this->isAllowed($this->_user, $value['resource'], $value['permission'])) { 
                $result[] = $value['permission']; 
            } 
        } 
 
        return $result; 
    } 
 
    public function isUserAllowed($resource, $permission) 
    { 
        return ($this->isAllowed($this->_user, $resource, $permission)); 
    }
    
	public function isUserAllowedCount($resource, $permission, $count) 
    { 
        $isAllowed = $this->isUserAllowed($resource, $permission);
        if(!$isAllowed)
        	return false;
        
        $role_id = $this->_getUserRoleId;
        $permission = $this->_db->fetchAll(
	        $this->_db->select()
	            ->from('acl_resources')
	            ->from('acl_permissions')
	            ->where('acl_resources.resource = "' . $resource . '"')
	            ->where('acl_permissions.role_id = "' . $role_id . '"')
	            ->where('acl_permissions.permission = "' . $permission . '"')
	            ->where('acl_permissions.count >= "' . $count . '"'));
        
         return $permission != null;
    }
}
?>