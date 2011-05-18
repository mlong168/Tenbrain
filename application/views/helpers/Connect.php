<?php

/**
 * LoginForm
 */

class Application_View_Helper_Connect extends Zend_Form {
    
    public function init() {

        $username = $this->createElement('text', 'username', array(
        					'class'	=>	'control input',
                            'required' => TRUE
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors');
        
        $email = $this->createElement('text', 'email', array(
        					'class'	=>	'control input',
                            'required' => TRUE
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors');
		
        $signup = $this->createElement('submit', 'submit', array(
                            'class' => 'login_submit underlined_dash'
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors')->setLabel('Sign In');
        
        $this->addElements(array(
                    $username,
                    $email,
                    $signup
        ));

    }
}