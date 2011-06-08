<?php
class Paypal_Validate_CVV2 extends Zend_Validate_Abstract
{
	const IS_EMPTY	= 1;
	const TOO_SHORT = 2;
	const NOT_DIGITS = 4;
	
	/**
	 * 
	 * CVV2 minimum lenght
	 * @var int
	 */
	const MINLEN = 3;
	
	/**
     * Digits filter used for validation
     *
     * @var Zend_Filter_Digits
     */
	protected static $_filter = null;
	
	 /**
     * Validation failure message template definitions
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::IS_EMPTY		=> "CVV2 card number is required.",
        self::TOO_SHORT 	=> "CVV2 code is too short.",
        self::NOT_DIGITS 	=> "CVV2 must contain only digits.",
    );
    
    public function isValid($value)
    {
    	if(empty($value))
    	{
    		$this->_error(self::IS_EMPTY);
            return false;
    	}
    	
    	if (strlen($value) < self::MINLEN)
    	{
    		$this->_error(self::TOO_SHORT);
            return false;
    	}
			
			if (null === self::$_filter) {
					require_once 'Zend/Filter/Digits.php';
					self::$_filter = new Zend_Filter_Digits();
			}

			if ($value !== self::$_filter->filter($value)) {
					$this->_error(self::NOT_DIGITS);
					return false;
			}
    	
    	return true;
    }
}
?>