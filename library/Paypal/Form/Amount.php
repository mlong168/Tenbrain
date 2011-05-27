<?php
class Paypal_Form_Amount extends Zend_Form
{
	public function init()
	{
		$time_amount = new Zend_Form_Element_Text('time_amount');
    	$time_amount->setValidators(array(new Zend_Validate_Digits()));
    	$time_amount->setDecorators(array('ViewHelper'));
    	$time_amount->setAttrib('value', '1');
//    	$this->form->time_form = $time_amount;

    	$money_amount = new Zend_Form_Element_Text('money_amount');
    	$money_amount->setValidators(array(new Zend_Validate_Digits()));
    	$money_amount->setDecorators(array('ViewHelper'));
    	$money_amount->setAttrib('value', '10');
//    	$this->form->amount_form = $money_amount;
    	
    	$submit = new Zend_Form_Element_Submit('submit');
    	$submit->setAttrib('class', 'login_submit underlined_dash');
    	$submit->setLabel('Enter Credit Card Info');
//    	$this->form->submit = $submit;

    	$this->addElements(array(
    		$time_amount,
    		$money_amount,
    		$submit,
    	));
	}
}
?>