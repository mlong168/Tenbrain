<?php
class Paypal_Validate_CreditCard extends Zend_Validate_CreditCard
{
	
	const IS_EMPTY = 'empty';
	
	public function __construct($options = null)
	{
		parent::__construct($options);
	}
	
	
	 /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::CHECKSUM       => "Credit card number seems to contain an invalid checksum.",
        self::CONTENT        => "Credit card number must contain only digits.",
        self::INVALID        => "Invalid type given. String expected.",
        self::LENGTH         => "Credit card number contains an invalid amount of digits.",
        self::PREFIX         => "Credit card number is incorrect.",
        self::SERVICE        => "Credit card number seems to be an invalid creditcard number.",
        self::SERVICEFAILURE => "Credit card number is incorrect.",
        self::IS_EMPTY 		 => "Credit card number is required.",
    );
    
    public function isValid($value)
    {
    	if(empty($value))
    	{
    		$this->_error(self::IS_EMPTY);
            return false;
    	}
    	return parent::isValid($value);
    }
    
    
}
?>