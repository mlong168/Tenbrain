<?php

class ConsoleController extends Zend_Controller_Action
{

	public function init()
	{
		/* set alternate layout */
		$helper = $this->_helper->getHelper('Layout');
		$layout = $helper->getLayoutInstance();
		
		$layout->setLayout('control_panel');
		$this->view->headTitle()->prepend('Tenbrain Control Panel');
		
		$scripts = array(
			'extjs4/ext-all-debug',
			// 'cp/instances',
			// 'cp/images',
			// 'cp/snapshots',
			// 'cp/profile',
			// 'cp/transferer'
		);
		
		// if($this->account_type === 'premium') $scripts = array_merge($scripts, array('cp/load_balancers', 'cp/elastic_ips'));
		
		$scripts []= 'cp/cp';
		
		foreach($scripts as $script)
		{
			$this->view->headScript()->appendFile("/js/{$script}.js", 'text/javascript');
		}
	}
	
	public function indexAction()
	{
		
	}

}