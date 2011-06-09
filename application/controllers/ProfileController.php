<?php
/**
 * ProfileController
 */

class ProfileController extends Zend_Controller_Action
{
	private $_acl = array();
	private $layout;
	private $user;
	
	function init()
	{
        $this->_acl = new ZendExt_ACL(Zend_Auth::getInstance()->getIdentity());
		if(!$this->_acl->isUserAllowed('Console','view'))
			$this->_redirect("account/sign_in");
		$this->user = Zend_Auth::getInstance()->getIdentity();
	}

    public function indexAction ()
    {
		$this->view->headTitle()->prepend('Tenbrain Profile Page');
		$user = Zend_Auth::getInstance()->getIdentity();
		$this->view->user = $user;
        
    }
    
	public function accountSettingsAction ()
    {
 
    }
    
	public function linkedAccountsAction ()
    {

    }
    
    public function accountPasswordAction() {
    	$form = new Application_View_Helper_ChangePassword();
    	$this->view->form = $form;
    	
    	if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
            	$accounts = new Application_Model_DbTable_Accounts();
            	$data = $form->getValues();
            	$accounts->change_password($this->user->id, md5($data['password']));
            	
            	$this->view->message = "Your password has been changed.";
            }
    	}
    }
}
