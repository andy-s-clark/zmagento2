<?php
class Buildcom_Webserv_Model_Resource_Product extends Buildcom_Webserv_Model_Resource_Webserv_Abstract
{
    protected function _construct()
    {
        $this->_init('products');
    }

    public function _getRequestUri($unique_id)
    {
        return $this->_services_uri . '/' . $this->_service . '/' . rawurlencode($unique_id);
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
                'entity_id' => array('data' => 'uniqueId'),
                'entity_type_id' => array('fixed' => 10), // TODO
                'attribute_set_id' => array('fixed' => 9), // TODO
                'type_id' => array('fixed' => 'simple'), // TODO
                'sku' => array('data' => 'sku'),
                'created_at' => array('data' => 'dateAdded', 'format' => 'datetime'),
                'updated_at' => array('data' => 'modifiedDate', 'format' => 'datetime'),
                'has_options' => array('fixed' => 0),
                'required_options' => array('fixed' => 0),
                'price' => array('data' => 'cost'), // TODO
                'cost' => '', // TODO Assuming webservices 'cost' is actually 'price'
                'weight' => array('data' => 'weight'), // TODO Do units match?
                'special_price' => NULL,
                'msrp' => NULL,
                'name' => array(), // Formatted value
                'meta_title' => NULL,
                'meta_description' => NULL,
                'image' => array('data' => 'image'),
                'small_image' => array('data' => 'image'),
                'url_key' => array(),
                'thumbnail' => array('data' => 'image'),
                'gift_message_available' => NULL,
                'url_path' => array(),
                'custom_design' => NULL,
                'options_container' => array('fixed' => 'container2'),
                'image_label' => array('result' => 'name'),
                'small_image_label' => array('result' => 'name'),
                'thumbnail_label' => array('result' => 'name'),
                'page_layout' => NULL,
                'country_of_manufacture' => NULL,
                'msrp_enabled' => array('fixed' => '2'),
                'msrp_display_actual_price_type' => array('fixed' => '4'),
                'description' => array('data' => 'description'),
                'meta_keyword' => NULL,
                'short_description' => array('data' => 'productTitle'),
                'custom_layout_update' => NULL,
                'manufacturer' => array('data' => 'manufacturer'),
                'color' => NULL,
                'status' => array('data' => 'status'), // Enum: 'stock' as 1 and 'discontinued' as 0
                'tax_class_id' => array('fixed' => '0'),
                'visibility' => array('fixed' => '4'),
                'enable_googlecheckout' => array('fixed' => '1'),
                'is_recurring' => array('fixed' => '0'),
                'special_date_from' => NULL,
                'special_date_to' => NULL,
                'custom_design_from' => NULL,
                'custom_design_to' => NULL,
                'news_from_date' => NULL,
                'news_to_date' => NULL,
                'group_price' => array(),
                'group_price_changed' => array('fixed' => 0),
                'tier_price' => array(),
                'tier_price_changed' => array('fixed' => 0),
                'media_gallery' => array(),
        );

        $manufacturer_url_key = rawurlencode(str_replace(' ', '-', strtolower($data->manufacturer)));
        $sku_url_key = rawurlencode(str_replace(' ', '-', strtolower($data->sku)));

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
                case 'name':
                    $value = ( empty($data->manufacturer) ? '' : $data->manufacturer )
                    . ' '
                    . ( empty($data->productId) ? '' : $data->productId );
                    break;

                case 'status':
                    $value = $value == 'stock' ? '1' : '0';
                    break;

                case 'image':
                    $value = "{$image_base_uri}/resized/x800/{$manufacturer_url_key}images/{$value}";
                    break;

                case 'small_image':
                    $value = "{$image_base_uri}/resized/330x320/{$manufacturer_url_key}images/{$value}";
                    break;

                case 'thumbnail':
                    $value = "{$image_base_uri}/resized/cropped/50x50/{$manufacturer_url_key}images/{$value}";
                    break;

                case 'url_key':
                    $value = rawurlencode("{$manufacturer_url_key}-{$sku_url_key}-p{$data->uniqueId}");
                    break;

                case 'url_path':
                    $value = rawurlencode("{$manufacturer_url_key}-{$sku_url_key}-p{$data->uniqueId}.html");
                    break;

                case 'group_price':
                case 'tier_price':
                    $value = array();
                    break;

                case 'media_gallery': // TODO
                    $value = array(
                    'images' => array(
                    'value_id' => '366',
                    'file' => $result['image'],
                    'label' => $result['name'],
                    'position' => '1',
                    'disabled' => '0',
                    'label_default' => $result['name'],
                    'position_default' => '1',
                    'disabled_default' => '0',
                    ),
                    'values' => array(),
                    );
                    break;

                default:
                    break;
            }

            $result[$mage_key] = $value;

            //$value = empty($data->$webserv_key) ? '' : $data->$webserv_key;

        }

        return $result;
    }
}