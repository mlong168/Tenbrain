<?php

class ProductsController extends Zend_Controller_Action
{

	public function init()
	{
		/* Initialize action controller here */
	}
	
	public function indexAction()
	{
		$this->_forward('pricing');
	}
	
	public function pricingAction()
	{
		$this->view->headTitle()->prepend('Pricing');
	}
	
	public function featuresAction()
	{
		$this->view->headTitle()->prepend('Features');
	}


}