<?php

class IndexControllerTest extends ControllerTestCase  
{

	public function testIndexGoesToTenstackSelection()
	{
        $this->dispatch("/");
        $this->assertController("selection");
        $this->assertAction("tenstack");	
		$this->assertResponseCode(200);
	}
	
}