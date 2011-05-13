<?php

class AuthController extends Zend_Controller_Action
{

	public function init() {
    }
    
    public function indexAction() {
        $this->_redirect('auth/login');
    }
    
    public function loginAction() {
    	//echo class_exists('Application_Model_DbTable_Accounts');
        $users = new Application_Model_DbTable_Accounts();
        $form = new Application_View_Helper_LoginForm();
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                $auth = Zend_Auth::getInstance();
                $authAdapter = new Zend_Auth_Adapter_DbTable($users->getAdapter(), 'accounts');
                $authAdapter->setIdentityColumn('username')
                        ->setCredentialColumn('password')
                        ->setIdentity($data['username'])
                        ->setCredential($data['password']);
                $result = $auth->authenticate($authAdapter);
                print_r($result);
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
        $users = new Application_Model_DbTable_Accounts();
        $form = new Application_View_Helper_RegistrationForm();
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                if ($data['password'] != $data['confirmPassword']) {
                    $this->view->errorMessage = 'Password and confirm password dont \' match';
                    return;
                }
                if ($users->isUnique($data['username'])) {
                    $this->view->errorMessage = 'Name already taken. Please choose another one.';
                    return;
                }
                unset($data['confirmPassword']);
                $users->insert($data);
                $this->_redirect('auth/login');
            }
        }
    }
    
    public function logoutAction() {
        $storage = new Zend_Auth_Storage_Session();
        $storage->clear();
        $this->_redirect('auth/login');
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

