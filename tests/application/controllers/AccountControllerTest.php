<?php

class AccountControllerTest extends ControllerTestCase
{

    public function testRegisterPageAvailable()
    {
        $this->dispatch("/account/sign_up");
        $this->assertController("account");
        $this->assertAction("sign_up");
		$this->assertResponseCode(200);
    }


}