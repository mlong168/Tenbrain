<?php

/**
 * RegistrationForm
 */
class Application_View_Helper_RegistrationForm extends Zend_Form {
    
    public function init() {
        $email = $this->createElement('text', 'email', array(
                            'label' => 'E-mail: *',
                            'required' => TRUE
        ));

        $username = $this->createElement('text', 'username', array(
                            'label' => 'Username: *',
                            'required' => TRUE
        ));

        $password = $this->createElement('password', 'password', array(
                            'label' => 'Password: *',
                            'required' => TRUE
        ));

        $confirmPassword = $this->createElement('password', 'confirmPassword', array(
                            'label' => 'Confirm Password: *',
                            'required' => TRUE
        ));

        $register = $this->createElement('submit', 'Register', array(
                            'label' => 'Sign Up'
        ));

        $this->addElements(array(
                    $email,
                    $username,
                    $password,
                    $confirmPassword,
                    $register
        ));
    }
}