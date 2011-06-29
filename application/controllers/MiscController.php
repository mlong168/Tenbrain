<?php

class MiscController extends Zend_Controller_Action
{

	public function init()
	{
		/* Initialize action controller here */
	}
	
	public function indexAction()
	{
		$this->_forward('apology');
	}
	
	public function apologyAction()
	{
		$this->view->headTitle()->prepend('Apology');
	}
	
	public function aboutAction()
	{
		$this->view->headTitle()->prepend('About us');
	}

	public function blogAction()
	{
		$this->view->headTitle()->prepend('Blog');
	}

}