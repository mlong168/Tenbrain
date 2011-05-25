<?php

class Application_Model_Provider_AmazonTest extends ControllerTestCase
{
	
	protected $amazon;
	
	public function setUp()
	{
		parent::setUp();		
		$this->amazon = new Application_Model_Provider_Amazon();
	}
	
	
	public function testCanLaunchServer()
	{
		$this->assertTrue(true);
	}
	
	
}
