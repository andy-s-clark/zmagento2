<?php
class Buildcom_Webserv_IndexController extends Mage_Core_Controller_Front_Action {
    // OMC Test siteid, product and category
    const OMC_SITE_ID = '33';
    const OMC_STORE_ID = '55';
    const OMC_PRODUCT_ID = '1573671';
    const MMC_CATEGORY_ID = '752';

    // Magento Test product and category
    const MAGE_PRODUCT_ENTITY_ID = 1;
    const MAGE_CATEGORY_ENTITY_ID = 3;

    public function indexAction() {
        $model = Mage::getModel('Buildcom_Webserv_Model_Product');
        $product = $model->load(self::OMC_PRODUCT_ID);
        $this->_vardumpAsTable($product->getData());
    }

    public function categoryAction() {
        $model = Mage::getModel('Buildcom_Webserv_Model_Category');
        $category = $model->load(self::MMC_CATEGORY_ID);
        $this->_vardumpAsTable($category->getData());
    }

    /**
     * Show product details as listed in OMC
     */
    public function omdirectAction() {
        $helper = Mage::helper('Buildcom_Webserv_Helper_Direct');

        echo '<h1>Tests</h2>' . PHP_EOL;

        echo '<h2>/categories/facetCategory</h2>' . PHP_EOL;
        $helper->setService('/categories/facetCategory')
            ->setPostData('siteId', self::OMC_SITE_ID)
            ->setPostData('categoryId', self::MMC_CATEGORY_ID);
        $results = $helper->execute();
        $this->_vardumpAsTable((array)$results);
        echo '<hr />' . PHP_EOL;

        echo '<h2>/products/keywordSearch</h2>' . PHP_EOL;
        $helper->setService('/products/keywordSearch')
            ->setQueryArg('updateCache', FALSE)
            ->setPostData('siteId', self::OMC_SITE_ID)
            ->setPostData('selectedFacetCriteriaList', array( // TODO
                3250 => 'rattan', // Material
            ))
            ->setPostData('pricebookId', 1) // TODO
            ->setPostData('discontinuedFilter', FALSE) // TODO
            ->setPostData('page', 1) // TODO (Page 0 or 1?)
            ->setPostData('pageSize', 3)
            ->setPostData('sortOption', 'SCORE')
            ->setPostData('productDropViewType', 'LIST')
            ->setPostData('keyword', '')
            ->setPostData('keyword', self::OMC_STORE_ID);
        $results = $helper->execute();
        $this->_vardumpAsTable((array)$results);
        echo '<hr />' . PHP_EOL;

        echo '<h2>/products/categorySearch</h2>' . PHP_EOL;
        $helper->setService('/products/categorySearch')
            ->setQueryArg('updateCache', FALSE)
            ->setPostData('siteId', self::OMC_SITE_ID)
            ->setPostData('selectedFacetCriteriaList', array( // TODO
                3250 => 'rattan', // Material
            ))
            ->setPostData('pricebookId', 1) // TODO
            ->setPostData('discontinuedFilter', FALSE) // TODO
            ->setPostData('page', 1) // TODO (Page 0 or 1?)
            ->setPostData('pageSize', 3)
            ->setPostData('sortOption', 'SCORE')
            ->setPostData('productDropViewType', 'LIST')
            ->setPostData('categoryId', self::MMC_CATEGORY_ID);
        $results = $helper->execute();
        $this->_vardumpAsTable((array)$results);
        echo '<hr />' . PHP_EOL;

        Magento_Profiler::start('omtimer');
        echo '<h2>/products/{uniqueId}</h2>' . PHP_EOL;
        $helper->setService('products/' . self::OMC_PRODUCT_ID)
            ->setQueryArg('updateCache', FALSE);
        //$helper->productId = '75050CB';
        //$helper->manufacturer = 'Delta';
        $results = $helper->execute();
        Magento_Profiler::stop('omtimer');
        $this->_vardumpAsTable((array)$results);
        echo '<hr />' . PHP_EOL;
    }

    /**
     * Show a catalog/product
     */
    public function catalogproductAction() {
        $model = Mage::getModel('Mage_Catalog_Model_Product');
        $product = $model->load(self::MAGE_PRODUCT_ENTITY_ID);
        $this->_vardumpAsTable($product->getData());
    }

    /**
     * Show a catalog/category
     */
    public function catalogcategoryAction() {
        $model = Mage::getModel('Mage_Catalog_Model_Category');
        $product = $model->load(self::MAGE_CATEGORY_ENTITY_ID);
        $this->_vardumpAsTable($product->getData());
    }

    /**
     * Product Collections
     * TODO
     */
    public function collectionAction() {
        $itemsCollection = Mage::getModel('Buildcom_Webserv_Model_Product')->getCollection()->load();
        //->addIdFilter(array(self::OMC_PRODUCT_ID))
        /*->addAttributeToFilter(array(
         array('attribute' => 'sku', '=' => 'BCI1573671'),
         ))*/
        //->addAttributeToSelect('url_key')
        foreach ($itemsCollection as $item) {
            //echo '<h2>' . htmlentities($item->getSku()) . '</h2>' . PHP_EOL;
            //$this->_vardumpAsTable($item->getData());
        }

        $itemsCollection = Mage::getModel('Buildcom_Webserv_Model_Category')->getCollection()->load();
        foreach ($itemsCollection as $item) {
            $this->_vardumpAsTable($item->getData());
        }
    }

    /**
     * Output data in a formatted table
     * @param array $data
     * @return boolean
     */
    protected function _vardumpAsTable($data) {
        if (!is_array($data)) {
            return FALSE;
        }

        echo '<table border="1">' . PHP_EOL;
        echo '	<tr>' . PHP_EOL;
        echo '		<th>Key</th>' . PHP_EOL;
        echo '		<th>Type</th>' . PHP_EOL;
        echo '		<th>Value</th>' . PHP_EOL;
        echo '	</tr>' . PHP_EOL;
        foreach ($data as $key => $value) {
            $type = gettype($value);
            echo '	<tr>' . PHP_EOL;
            echo '		<td>' . htmlentities($key) . '</td>' . PHP_EOL;
            echo '		<td>' . htmlentities($type) . '</td>' . PHP_EOL;
            echo '		<td>';
            if (empty($type) || $type == 'array' || $type == 'object') {
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
