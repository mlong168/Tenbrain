<?php
class Paypal_Form_Amount extends Zend_Form
{
	public function init()
	{
			/*$time_amount = new Zend_Form_Element_Text('time_amount');
    	$time_amount->setValidators(array(new Zend_Validate_Digits()));
    	$time_amount->setDecorators(array('ViewHelper'));
			$time_amount->setAttrib("class", "control paypal");
			$time_amount->setAttrib("width", "30px");
    	$time_amount->setValue(10);

    	$money_amount = new Zend_Form_Element_Text('money_amount');
    	$money_amount->setValidators(array(new Zend_Validate_Digits()));
    	$money_amount->setDecorators(array('ViewHelper'));
			$money_amount->setAttrib("class", "control paypal");
			$money_amount->setAttrib("width", "30px");
    	$money_amount->setValue(10);*/
			
			$payment_type = new Zend_Form_Element_Radio('payment_type');
			$payment_type->addMultiOptions(array(
				"single_day_pay" => "$1 a day",
				"fiver" => "$5",
				"ten_micro" => "$10",
				"ten_up" => "> $10"
			));
			
			$tenup_amount = new Zend_Form_Element_Select('tenup_amount');
			$tenup_amount->addMultiOptions(array(
				"15" => "$15",
				"20" => "$20",
				"25" => "$25",
				"30" => "$30"
			));
			
    	$submit = new Zend_Form_Element_Submit('submit');
    	$submit->setAttrib('class', 'login_submit underlined_dash');
    	$submit->setLabel('Enter Credit Card Info');

    	$this->addElements(array(
    		//$time_amount,
    		//$money_amount,
				$payment_type,
				$tenup_amount,
    		$submit,
    	));
	}
}
?>