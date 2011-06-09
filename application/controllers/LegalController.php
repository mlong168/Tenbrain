<?php

class LegalController extends Zend_Controller_Action
{

	public function init()
	{
		/* Initialize action controller here */
	}
	
	public function indexAction()
	{
		$this->_forward('terms');
	}
	
	public function termsAction()
	{
		$this->view->headTitle()->prepend('Terms Of Service');
	}
	
	public function privacyAction()
	{
		$this->view->headTitle()->prepend('Privacy Policy');
	}
	
	public function refundAction()
	{
		$this->view->headTitle()->prepend('Refund Policy');
	}


}