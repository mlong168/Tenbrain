<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
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
				'controller'	=> 'paypal',
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
