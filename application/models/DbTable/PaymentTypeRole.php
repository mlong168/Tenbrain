<?php
/**
 * PaymentTypeRoleModel
 * 
 * @author Yevgebn Vershynin
 * @version 
 */
class Application_Model_DbTable_PaymentTypeRole extends Zend_Db_Table_Abstract
{
	/**
	 * The table name 
	 */
	protected $_name = 'payment_type_role';
	
	
	public function getPaymentType($payment_type)
	{
		$select = $this->select()->where("payment_type = ?", $payment_type);
		return $this->fetchRow($select);
	}
}