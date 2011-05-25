<?php

/**
 * LoginForm
 */

class Application_View_Helper_LoginForm extends Zend_Form {
    
    public function init() {

        $login = $this->createElement('text', 'login', array(
        					'class'	=>	'control input',
                            'required' => TRUE,
        					'validators' => array(
					            array('NotEmpty', false, array(
					                'messages' => array('isEmpty' => 'The Username/Email field is required.')
					            )),
					        )
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors');

        $password = $this->createElement('password', 'password', array(
        					'class'	=>	'control input',
                            'required' => TRUE,
        					'validators' => array(
					            array('NotEmpty', false, array(
					                'messages' => array('isEmpty' => 'The Password field is required.')
					            )),
					        )
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors');
		
        $rememberme = $this->createElement('checkbox','remember', array(
        					'class'	=>	'checkbox'
        ))->setDecorators(array('ViewHelper')); 
		
        $signin = $this->createElement('submit', 'submit', array(
                            'class' => 'login_submit underlined_dash'
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors')->setLabel('Sign In');
        
        $this->addElements(array(
                    $login,
                    $password,
                    $rememberme,
                    $signin
        ));

    }
}