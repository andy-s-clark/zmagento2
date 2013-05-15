<?php
/**
 * Add stock information to product
 *
 * @param   Varien_Event_Observer $observer
 * @return  Mage_CatalogInventory_Model_Observer
 */
class Buildcom_Webserv_Model_Observer
{
    public function addInventoryData($observer)
    {
        $product = $observer->getEvent()->getProduct();
        if ($product instanceof Buildcom_Webserv_Model_Product) {
            $productId = intval($product->getId());
            if (!isset($this->_stockItemsArray[$productId])) {
                $this->_stockItemsArray[$productId] = Mage::getModel('Mage_CatalogInventory_Model_Stock_Item');
            }
            $productStockItem = $this->_stockItemsArray[$productId];
            $productStockItem->assignProduct($product);
            $product->setData('is_salable', 1); // Forced
        }
        return $this;
    }
}
