<?php

/**
 * RegistrationForm
 */
class Application_View_Helper_RegistrationForm extends Zend_Form {
    
    public function init() {
        $email = $this->createElement('text', 'email', array(
                            'required' => TRUE,
        					'class'	=>	'control input'
        ))->addValidator('EmailAddress',true)->setDecorators(array('ViewHelper'))->addDecorator('Errors')->addFilters(array('StringTrim', 'StripTags'))
->addErrorMessage('The Email address is invalid.');
         
        $username = $this->createElement('text', 'username', array(
                            'required' => TRUE,
        					'class'	=>	'control input',
					        'validators' => array(
					            array('NotEmpty', false, array(
					                'messages' => array('isEmpty' => 'The Username field is required.')
					            )),
					        )
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors');

        $password = $this->createElement('password', 'password', array(
                            'required' => TRUE,
        					'class'	=>	'control input',
        					'validators' => array(
					            array('NotEmpty', false, array(
					                'messages' => array('isEmpty' => 'The Password field is required.')
					            )),
					        )
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors')->addValidator('StringLength', false,array(6,20));
        
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
                ))->removeDecorator('Errors') ;
		
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