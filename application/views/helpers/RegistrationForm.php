<?php

/**
 * RegistrationForm
 */
class Application_View_Helper_RegistrationForm extends Zend_Form {
    
    public function init() {
        $email = $this->createElement('text', 'email', array(
                            'required' => TRUE,
        					'class'	=>	'control input'
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors');
        
        $username = $this->createElement('text', 'username', array(
                            'required' => TRUE,
        					'class'	=>	'control input'
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors');

        $password = $this->createElement('password', 'password', array(
                            'required' => TRUE,
        					'class'	=>	'control input'
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors');
        
        $register = $this->createElement('submit', 'submit', array(
                            'class' => 'login_submit underlined_dash'
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors')->setLabel('Create my account');

		$recaptchaKeys = Zend_Registry::get('config.recaptcha');
		
        $recaptcha = new Zend_Service_ReCaptcha($recaptchaKeys["pubkey"], $recaptchaKeys["privkey"]);
        $recaptcha->setParams(array(
		    'xhtml'=>true
		));
		$recaptcha->setOptions(array(
		    'theme'=>'white',
		    'tabindex'=>2
		));
        $captcha = $this->createElement('Captcha', 'ReCaptcha',
                array('captcha'=>array('captcha'=>'ReCaptcha',
                                        'service'=>$recaptcha)
                ));
		
        $this->addElements(array(
                    $email,
                    $username,
                    $password,
                    $confirm_password,
                    $captcha,
                    $register
        ));
    }
}