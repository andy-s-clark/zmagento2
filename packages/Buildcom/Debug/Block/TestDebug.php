<?php
class Buildcom_Debug_Block_TestDebug extends Mage_Core_Block_Template
{
    protected function _construct()
    {
        parent::_construct();
    }

    protected function _toHtml()
    {
        $this->setTemplate('test.phtml');
        $this->assign(array(
			'itemzero' => 'foobar',
        ));

        return parent::_toHtml();
    }
}