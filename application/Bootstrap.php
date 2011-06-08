<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
	protected function _initSession()
	{
		$dbconfig = $this->getOption('resources');
		$config['db'] = $dbconfig['db'];
		$config['SaveHandler'] = array(
			'name'    => 'sessions', //table name as per Zend_Db_Table
			'primary' => array(
				'id',   //the sessionID given by PHP
				'path', //session.save_path
				'name', //session name
			),
			'primaryAssignment' => array(
				//you must tell the save handler which columns you
				//are using as the primary key. ORDER IS IMPORTANT
				'sessionId', //first column of the primary key is of the sessionID
				'sessionSavePath', //second column of the primary key is the save path
				'sessionName', //third column of the primary key is the session name
			),
			'modifiedColumn' => 'modified', //time the session should expire
			'dataColumn'     => 'data',     //serialized data
			'lifetimeColumn' => 'lifetime', //end of life for a specific record
		);

		$config['lifetime'] = 60*60*24*30;

		$config['options'] = array (
							  'bug_compat_42' => '',
							  'bug_compat_warn' => '',
							  'cache_expire' => '180',
							  'cache_limiter' => 'nocache',
							  'cookie_domain' => '',
							  'cookie_httponly' => '',
							  'cookie_lifetime' => $config['lifetime'],
							  'cookie_path' => '/',
							  'cookie_secure' => '0',
							  'entropy_file' => '',
							  'entropy_length' => '0',
							  'gc_divisor' => '1000',
							  'gc_maxlifetime' => '1440',
							  'gc_probability' => '1',
							  'hash_bits_per_character' => '5',
							  'hash_function' => '0',
							  'name' => 'PHPSESSID',
							  'referer_check' => '',
							  'save_handler' => 'user',
							  'save_path' => '',
							  'serialize_handler' => 'php',
							  'use_cookies' => '1',
							  'use_only_cookies' => 'on',
							  'use_trans_sid' => '0',
							  'strict' => false,
							  'remember_me_seconds' => $config['lifetime'],
							  'throw_startup_exceptions' => true,
		);

		$db = Zend_Db::factory($config['db']['adapter'],$config['db']['params']);

		Zend_Db_Table_Abstract::setDefaultAdapter($db);
		Zend_Session::setSaveHandler(new Zend_Session_SaveHandler_DbTable($config['SaveHandler']));
		Zend_Session::setOptions($config['options']);
		Zend_Session::start();
	}
	
    protected function _initDoctype()
    {
        $this->bootstrap('view');
		$view = $this->getResource('view');
		$view->doctype('XHTML1_TRANSITIONAL');
		
		$view->headTitle('TenBrain - Save your Brain, While saving Money!')
             ->setSeparator(' - ');
		
		$front_controller = Zend_Controller_Front::getInstance();
		$dispatcher = $front_controller->getDispatcher();
		$dispatcher->setWordDelimiter(array('-', '.', '_'));
		
		unset($front_controller);
	}
	
    protected function _initRoutes()
    {
		$front_controller = Zend_Controller_Front::getInstance();
		$router = $front_controller->getRouter();
		
		$router->addRoute('default_selection', new Zend_Controller_Router_Route('', array(
			'controller'	=> 'selection',
			'action'		=> 'index'
		)));
		
		$router->addRoute('apology', new Zend_Controller_Router_Route('apology', array(
			'controller'	=> 'misc',
			'action'		=> 'apology'
		)));
		
		$router->addRoute('about', new Zend_Controller_Router_Route('about', array(
			'controller'	=> 'misc',
			'action'		=> 'about'
		)));
		
		$router->addRoute('paypal_save', new Zend_Controller_Router_Route(
			'/pay/details/:id', 
			array(
				'controller'	=> 'pay',
				'action'		=> 'details'
		)));
		
		unset($front_controller);
    }
	
    protected function _initAutoload()
	{
	    $this->options = $this->getOptions();
	    Zend_Registry::set('config.recaptcha', $this->options['recaptcha']);
	}
	
	protected function _initAuth()
	{
        $this->bootstrap('layout');
		$layout = $this->getResource('layout');
		

		$auth = Zend_Auth::getInstance();
		$logged_in = $auth->hasIdentity();
		
		$layout->logged_in = $logged_in;
		if($logged_in)
		{
			$layout->username = $auth->getIdentity()->username;
		}
		else
		{
			$layout->third_party_auth_providers = array('google', 'twitter', 'facebook');
		}
	}
}
