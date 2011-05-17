<?php

/**
 * LoginForm
 */

class Application_View_Helper_LoginForm extends Zend_Form {
    
    public function init() {

        $username = $this->createElement('text', 'username', array(
        					'class'	=>	'control input',
                            'required' => TRUE
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors');

        $password = $this->createElement('password', 'password', array(
        					'class'	=>	'control input',
                            'required' => TRUE
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors');
		
        $rememberme = $this->createElement('checkbox','remember', array(
        					'class'	=>	'checkbox'
        ))->setDecorators(array('ViewHelper')); 
		
        $signin = $this->createElement('submit', 'submit', array(
                            'class' => 'login_submit underlined_dash'
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors')->setLabel('Sign In');
        
        $this->addElements(array(
                    $username,
                    $password,
                    $rememberme,
                    $signin
        ));

    }
}