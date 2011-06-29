<?php
/*
PayPal monthly payments form
author: WhiteDemon
*/
class Paypal_Form_Monthly extends Zend_Form
{
	public function init()
	{
		$payment_type = new Zend_Form_Element_Radio('payment_type');
		$payment_type->addMultiOptions(array(
			"some_features" => "59.99",
			"more_features" => "1",
			"all_features" => "1"			
		));

		$submit = new Zend_Form_Element_Submit('submit');
    	$submit->setAttrib('class', 'login_submit underlined_dash');
    	$submit->setLabel('Enter Credit Card Info');

		$this->addElements(array(
			$payment_type,
			$submit
		));
	}
}
?>