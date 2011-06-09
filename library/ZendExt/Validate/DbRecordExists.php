<?php

class ZendExt_Validate_DbRecordExists extends Zend_Validate_Abstract {

    const NOT_EXISTS = 'notExists';

    protected $_messageTemplates = array(
        self::NOT_EXISTS => "This '%value%' email is not exists"
    );

    private $_table;
    private $_columnName;

    public function __construct(Zend_Db_Table_Abstract $table, $columnName) {
        $this->_table = $table;
        $this->_columnName = $columnName;
    }

    public function isValid($value) {
        $this->_setValue($value);
        $where = $this->_table->getAdapter()->quoteInto("{$this->_columnName} = ?", $value);
        $row = $this->_table->fetchRow($where);
        if ($row == null) {
            $this->_error(self::NOT_EXISTS);
            return false;
        }
        return true;
    }
}