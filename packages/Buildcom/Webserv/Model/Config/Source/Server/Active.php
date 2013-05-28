<?php
class Buildcom_Webserv_Model_Config_Source_Server_Active implements Mage_Core_Model_Option_ArrayInterface
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => 'development',
                'label' => Mage::helper('Buildcom_Webserv_Helper_Data')->__('Development'),
            ),
            array(
                'value' => 'production',
                'label' => Mage::helper('Buildcom_Webserv_Helper_Data')->__('Production'),
            ),
        );
    }
}
