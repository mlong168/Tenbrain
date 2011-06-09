<?php

/**
 * Forgot
 */

class Application_View_Helper_Forgot extends Zend_Form {
    
    public function init() {
    	$accounts = new Application_Model_DbTable_Accounts();
    	$dbRecordExists = new ZendExt_Validate_DbRecordExists($accounts, 'email');
        
        $email = $this->createElement('text', 'email', array(
        					'class'	=>	'control input',
                            'required' => TRUE,
        					'validators' => array(
					            array('NotEmpty', false, array(
					                'messages' => array('isEmpty' => 'The Email field is required.')
					            )),
					        )
        ))->addValidator('EmailAddress',true)->addValidator($dbRecordExists)->setDecorators(array('ViewHelper'))->addDecorator('Errors');
        
        $signup = $this->createElement('submit', 'submit', array(
                            'class' => 'login_submit underlined_dash'
        ))->setDecorators(array('ViewHelper'))->addDecorator('Errors')->setLabel('Sign In');
        
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
                    $captcha,
                    $email,
                    $signup
        ));

    }
}