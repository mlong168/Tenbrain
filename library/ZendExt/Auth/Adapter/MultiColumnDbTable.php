<?php

class ZendExt_Auth_Adapter_MultiColumnDbTable extends Zend_Auth_Adapter_DbTable
{
  protected $_alternativeIdentityColumn = null;
 
  protected function _authenticateCreateSelect()
  {
    $select = parent::_authenticateCreateSelect();
 
    if(isset($this->_alternativeIdentityColumn))
    {
      $select->orWhere($this->_zendDb->quoteIdentifier(
                      $this->_alternativeIdentityColumn, true) . ' = ?',
                      $this->_identity);
    }
 
    return $select;
  }
 
  public function setAlternativeIdentityColumn($alternativeIdentityColumn)
  {
    $this->_alternativeIdentityColumn = $alternativeIdentityColumn;
    return $this;
  }
}

?>