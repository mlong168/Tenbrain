<?php

class AuthController extends Zend_Controller_Action
{

	public function init() {
    }
    
    public function indexAction() {
        $this->_redirect('auth/login');
    }
    
    public function loginAction() {
        $accounts = new Application_Model_DbTable_Accounts();
        $form = new Application_View_Helper_LoginForm();
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                $auth = Zend_Auth::getInstance();
                $authAdapter = new Zend_Auth_Adapter_DbTable($accounts->getAdapter(), 'accounts');
                $authAdapter->setIdentityColumn('username')
                        ->setCredentialColumn('password')
                        ->setIdentity($data['username'])
                        ->setCredential(md5($data['password']));
                $result = $auth->authenticate($authAdapter);
                if ($result->isValid()) {
                    $storage = new Zend_Auth_Storage_Session();
                    $storage->write($authAdapter->getResultRowObject());
                    $this->_redirect('auth/profile');
                } else {
                    $this->view->errorMessage = 'Invalid username or password. Please try again';
                }
            }
        }
    }
    
    public function registerAction() {
        $accounts = new Application_Model_DbTable_Accounts();
        $form = new Application_View_Helper_RegistrationForm();
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                if ($data['password'] != $data['confirmPassword']) {
                    $this->view->errorMessage = 'Password and confirm password dont \' match';
                    return;
                }
                if ($accounts->isUnique($data['username'])) {
                    $this->view->errorMessage = 'Name already taken. Please choose another one.';
                    return;
                }
                $ip = null;
           		if ( isset($_SERVER["REMOTE_ADDR"]) ) { 
					$ip = $_SERVER["REMOTE_ADDR"]; 
				} else if ( isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ) { 
					$ip = $_SERVER["HTTP_X_FORWARDED_FOR"]; 
				} else if ( isset($_SERVER["HTTP_CLIENT_IP"]) ) { 
					$ip = $_SERVER["HTTP_CLIENT_IP"]; 
				}
                $data['password'] = md5($data['password']);
                $data['ip'] = $ip;
                unset($data['confirmPassword']);
                $accounts->insert($data);
                $this->_redirect('auth/login');
            }
        }
    }
    
    public function logoutAction() {
        $storage = new Zend_Auth_Storage_Session();
        $storage->clear();
        $this->_redirect('auth/login');
    }
    
	public function forgotAction() {
    }
    
    public function profileAction() {
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();
        if (!$data) {
            $this->_redirect('auth/login');
        }
        $this->view->username = $data->username;
    }


}

