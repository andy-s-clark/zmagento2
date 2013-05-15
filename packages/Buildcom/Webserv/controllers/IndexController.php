<?php
class Buildcom_Webserv_IndexController extends Mage_Core_Controller_Front_Action
{
    const OMC_UNIQUE_ID='1573671'; // Test product from OMC
    const MAGE_ENTITY_ID = 1; // Test product

    public function indexAction()
    {
        $model = Mage::getModel('Buildcom_Webserv_Model_Product');
        $product = $model->load(self::OMC_UNIQUE_ID);
        $this->_vardumpAsTable($product->getData());
    }

    /**
     * Show product details as listed in OMC
     */
    public function omdirectAction()
    {
        $helper = Mage::helper('Buildcom_Webserv_Helper_Data');
        $helper->setService('products/' . self::OMC_UNIQUE_ID);
        //$helper->productId = '75050CB';
        //$helper->manufacturer = 'Delta';
        $product = $helper->execute();
        $this->_vardumpAsTable((array)$product);
    }

    /**
     * Show a catalog/product
     */
    public function catalogproductAction()
    {
        $model = Mage::getModel('Mage_Catalog_Model_Product');
        $product = $model->load(self::MAGE_ENTITY_ID);
        $this->_vardumpAsTable($product->getData());
    }

    /**
     * Product Collections
     * TODO
     */
    public function collectionAction() {
        $itemsCollection = Mage::getModel('Buildcom_Webserv_Model_Product')
            ->getCollection()
            ->load();
            //->addIdFilter(array(self::OMC_UNIQUE_ID))
            /*->addAttributeToFilter(array(
            		array('attribute' => 'sku', '=' => 'BCI1573671'),
            ))*/
            //->addAttributeToSelect('url_key')

        foreach ( $itemsCollection as $item ) {
            //echo '<h2>' . htmlentities($item->getSku()) . '</h2>' . PHP_EOL;
            $this->_vardumpAsTable($item->getData());
        }
    }

    /**
     * Output data in a formatted table
     * @param array $data
     * @return boolean
     */
    protected function _vardumpAsTable($data)
    {
        if ( ! is_array($data) ) {
            return FALSE;
        }

        echo '<table border="1">' . PHP_EOL;
        echo '	<tr>' . PHP_EOL;
        echo '		<th>Key</th>' . PHP_EOL;
        echo '		<th>Type</th>' . PHP_EOL;
        echo '		<th>Value</th>' . PHP_EOL;
        echo '	</tr>' . PHP_EOL;
        foreach ( $data as $key => $value ) {
            $type = gettype($value);
            echo '	<tr>' . PHP_EOL;
            echo '		<td>' . htmlentities($key) . '</td>' . PHP_EOL;
            echo '		<td>' . htmlentities($type) . '</td>' . PHP_EOL;
            echo '		<td>';
            if ( empty($type) || $type == 'array' || $type == 'object' ) {
                echo '<div style="width: 300px; overflow: auto;">' . PHP_EOL;
                var_dump($value);
                echo '</div>' . PHP_EOL;
            } else {
                echo htmlentities($value) . PHP_EOL;
            }
            echo '		</td>';
            echo '	</tr>' . PHP_EOL;
        }
        echo '</table>' . PHP_EOL;
        return TRUE;
    }
}