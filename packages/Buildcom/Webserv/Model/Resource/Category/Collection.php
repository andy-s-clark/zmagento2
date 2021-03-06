<?php
/**
 * @see Mage_Core_Model_Resource_Db_Collection_Abstract
 */
class Buildcom_Webserv_Model_Resource_Category_Collection extends Buildcom_Webserv_Model_Resource_Collection_Abstract
{
    protected function _construct()
    {
        // Model, Resource Model
        $this->_init('Buildcom_Webserv_Model_Category', 'Buildcom_Webserv_Model_Resource_Category');
    }

    public function getData()
    {
        if ($this->_data === null) {
            $this->_renderFilters()
            ->_renderOrders()
            ->_renderLimit();
            /**
             * Prepare select for execute
             * @var string $query
             */
            $this->_data = array();
            foreach ( $this->_get_request_fake() as $result ) {
                $this->_data[] = $this->_resource->_mapData($result);
            }
            $this->_afterLoadData();
        }
        return $this->_data;
    }

    /**
     * Execute fake get request.
     * Just faking it for products until facetted web services are available.
     */
    protected function _get_request_fake() {
        $results = array();
        foreach (array(15, 16) as $item_id) {
            if ( $result = $this->_get_request($this->_resource->_getRequestUri($item_id)) ) {
                $results[] = $result;
            }
        }
        return $results;
    }
}