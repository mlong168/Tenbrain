<?php

class AuthControllerTest extends ControllerTestCase
{

    public function testRegisterPageAvailable()
    {
        $this->dispatch("/auth/register");
        $this->assertController("auth");
        $this->assertAction("register");	
		$this->assertResponseCode(404);
    }


}