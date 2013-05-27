<?php
class Buildcom_Webserv_Model_Resource_Category extends Buildcom_Webserv_Model_Resource_Webserv_Abstract
{
    protected function _construct()
    {
        $this->_init('categories');
    }

    public function _getRequestUri($unique_id)
    {
        return $this->_services_uri . '/' . $this->_service . '/items?siteId=33&categoryId=' . rawurlencode($unique_id);
    }

    /**
     * Map JSON object data to a Magento array
     *
     * @param object $data
     * @return array
     */
    public function _mapData($data)
    {
        $image_base_uri = 'http://sandbox.pullsdirect.com/imagebase';
        $map = array(
                // Magento Key => array('data' => Web Services Attribute),
                // Magento Key => array('result' => Previous result), // TODO Come up with a better name than result
                // Magento Attribute => array('fixed' => fixed_value), // TODO Most fixed values are just temporary hacks
                // Magento Key => NULL, // Output will be NULL unless an array() is used
                'entity_id' => array('data' => 'categoryId'),
                'entity_type_id' => array('fixed' => 3), // TODO
                'attribute_set_id' => array('fixed' => 3), // TODO
                'parent_id' => array('fixed' => 2), // TODO
                'created_at' => array('fixed' => time()*1000, 'format' => 'datetime'), // TODO Not present in OMC
                'updated_at' => array('fixed' => time()*1000, 'format' => 'datetime'), // TODO Not present in OMC
                'path' => array('fixed' => '1'), // TODO Should reflect category tree path. Needs work below.
                'position' => array('data' => 'orderBy'),
                'level' => array('fixed' => 2), // TODO
                'children_count' => array('fixed' => 0), // TODO
                'name' => array('data' => 'categoryName'),
                'url_key' => array('data' => 'slug'),
                'meta_title' => array('data' => 'metaTitle'),
                'display_mode' => array('fixed' => 'PRODUCTS'),
                'custom_design' => array('fixed' => ''),
                'page_layout' => array('fixed' => ''),
                'default_sort_by' => array('fixed' => ''),
                'url_path' => array('data' => 'slug'),
                'is_active' => array('fixed' => '1'), // TODO
                'include_in_menu' => array('fixed' => '1'), // TODO
                'is_anchor' => array('fixed' => '0'), // TODO
                'custom_use_parent_settings' => array('fixed' => '0'),
                'custom_apply_to_products' => array('fixed' => '0'),
                'description' => array('data' => 'description2'),
                'meta_keywords' => array('data' => 'metaKeywords'),
                'meta_description' => array('data' => 'metaDescription'),
                'custom_layout_update' => array('fixed' => ''),
                'available_sort_by' => array('fixed' => ''),
                'custom_design_from' => array('fixed' => ''),
                'custom_design_to' => array('fixed' => ''),
                'filter_price_range' => array('fixed' => ''),
                'custom_layout_update' => array('fixed' => ''),
        );

        $result = array();
        foreach ( $map as $mage_key => $source ) {
            $value = NULL;
            if ( isset($source) && is_array($source) ) {

                // Source of value
                switch ( TRUE ) {
                    case isset($source['data']):
                        $value = empty($data->$source['data']) ? '' : $data->$source['data'];
                        break;

                    case isset($source['fixed']):
                        $value = $source['fixed'];
                        break;

                    case isset($source['result']):
                        $value = empty($result[$source['result']]) ? '' : $result[$source['result']];
                        break;

                    default:
                        $value = '';
                        break;
                }

                // Formats
                switch ( empty($source['format']) ? FALSE : $source['format'] ) {
                    case 'datetime':
                        $value = $this->formatDate($value/1000); // OMC Webservices uses microseconds
                        break;

                    default:
                        break;
                }

                // Magento uses strings with 4 decimals for floats and doubles
                if ( is_float($value) || is_double($value) ) {
                    $value = number_format($value, 4);
                }
            }

            // Special cases
            switch (isset($value) ? $mage_key : FALSE) {
                case 'url_path':
                    $value = "{$value}.html";
                    break;

                case 'path':
                    $value = "{$value}/{$result['parent_id']}/{$result['entity_id']}";
                    break;

                default:
                    break;
            }

            $result[$mage_key] = $value;

        }

        return $result;
    }
}