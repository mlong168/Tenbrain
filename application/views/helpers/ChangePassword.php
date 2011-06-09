<?php

/**
 * Change Password
 */

class Application_View_Helper_ChangePassword extends Zend_Form {
    
    public function init() {
		$password = new Zend_Form_Element_Password('password');
	    $password->addValidator('StringLength', false, array(8,24))
	            ->setLabel('Choose your password:')
	            ->setRequired(true);
	
	    $password2 = new Zend_Form_Element_Password('password-confirm');
	    $password2->addValidator('StringLength', false, array(8,24))
	            ->setLabel('Confirm your password:')
	            ->addValidator(new Zend_Validate_Identical($_POST['password']))
	            ->setRequired(true);
	            
	    $submit = new Zend_Form_Element_Submit('submit');
	    $submit->class = "login_submit";
	    $submit->setLabel('Change');
	    
	    $this->addElements(array(
                    $password,
                    $password2,
                    $submit
        ));
    }
}