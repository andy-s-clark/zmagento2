<?php
class Buildcom_Webserv_Model_Resource_Product extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_setMainTable('test_product');
    }
}