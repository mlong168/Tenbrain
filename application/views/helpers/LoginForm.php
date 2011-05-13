<?php

/**
 * LoginForm
 */
class Application_View_Helper_LoginForm extends Zend_Form {
    
    public function init() {

        $username = $this->createElement('text', 'username', array(
                            'label' => 'Username: *',
                            'required' => TRUE
        ));

        $password = $this->createElement('password', 'password', array(
                            'label' => 'Password',
                            'required' => TRUE
        ));
        
        $signin = $this->createElement('submit', 'SignIn', array(
                            'label' => 'Sign In'
        ));

        $this->addElements(array(
                    $username,
                    $password,
                    $signin
        ));

    }
}