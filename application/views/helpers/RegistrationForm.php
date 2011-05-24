<?php

/**
 * RegistrationForm
 */
class Application_View_Helper_RegistrationForm extends Zend_Form {
    
    public function init() {
        $email = $this->createElement('text', 'email', array(
                            'required' => TRUE,
        					'class'	=>	'control input',
					        'validators' => array(
					            array('EmailAddress', false, array(
					                'messages' => array(
					                	'emailAddressInvalidFormat' => 'The Email address is invalid.'
					                
									)
					            ),
								array('NotEmpty', false, array(
					                'messages' => array(Zend_Validate_NotEmpty::IS_EMPTY => 'The Email field is required.')
					            ))),
					        )
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors');
         
        $username = $this->createElement('text', 'username', array(
                            'required' => TRUE,
        					'class'	=>	'control input',
					        'validators' => array(
					            array('NotEmpty', false, array(
					                'messages' => array('isEmpty' => 'The Username field is required.')
					            )),array('Alnum', false, array(
					                'messages' => array(
                   						Zend_Validate_Alnum::NOT_ALNUM => 'The Username must be composed of letters and numbers only'
									)
					            ))
					        )
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors');

        $password = $this->createElement('password', 'password', array(
                            'required' => TRUE,
        					'class'	=>	'control input',
        					'validators' => array(
					            array('NotEmpty', false, array(
					                'messages' => array(Zend_Validate_NotEmpty::IS_EMPTY => 'The Password field is required.')
					            )),
					        )
        ))->addValidator('StringLength', false, array('min' => 5, 'max' => 20, 'messages' => array(
			Zend_Validate_StringLength::TOO_SHORT => "'%value%' is less than %min% characters long",
        	Zend_Validate_StringLength::TOO_LONG  => "'%value%' is more than %max% characters long",
		)))->setDecorators(array('ViewHelper'))->addDecorator('Errors');
        
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
                    $captcha,
                    $register
        ));
    }
}