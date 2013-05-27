<?php
class Buildcom_Webserv_Model_Category extends Buildcom_Webserv_Model_Abstract
{
    /**
     * Entity code.
     * Can be used as part of method name for entity processing
     */
    const ENTITY                 = 'webserv_category';

    const CACHE_TAG              = 'webserv_category';
    protected $_cacheTag         = 'webserv_category';
    protected $_eventPrefix      = 'webserv_category';
    protected $_eventObject      = 'category';
    protected $_canAffectOptions = false;

    /**
     * Product type singleton instance
     *
     * @var Mage_Catalog_Model_Product_Type_Abstract
     */
    protected $_typeInstance = null;

    /**
     * Product link instance
     *
     * @var Mage_Catalog_Model_Product_Link
     */
    protected $_linkInstance;

    /**
     * Product object customization (not stored in DB)
     *
     * @var array
     */
    protected $_customOptions = array();

    /**
     * Product Url Instance
     *
     * @var Mage_Catalog_Model_Product_Url
     */
    protected $_urlModel = null;

    protected static $_url;
    protected static $_urlRewrite;

    protected $_errors = array();

    protected $_optionInstance;

    protected $_options = array();

    /**
     * Product reserved attribute codes
     */
    protected $_reservedAttributes;

    /**
     * Flag for available duplicate function
     *
     * @var boolean
     */
    protected $_isDuplicable = true;

    /**
     * Flag for get Price function
     *
     * @var boolean
     */
    protected $_calculatePrice = true;

    /**
     * @param Mage_Core_Model_Context $context
     * @param Buildcom_Webserv_Model_Resource_Product $resource
     * @param Buildcom_Webserv_Model_Resource_Product_Collection $resourceCollection
     * @param array $data
     */
    /* TODO
    public function __construct(
            Mage_Core_Model_Context $context,
            Buildcom_Webserv_Model_Resource_Product $resource,
            Buildcom_Webserv_Model_Resource_Product_Collection $resourceCollection,
            array $data = array()
    ) {
        parent::__construct($context, $resource, $resourceCollection, $data);
    }*/


    protected function _construct()
    {
        $this->_init('Buildcom_Webserv_Model_Resource_Category');
    }


    /**
     * Retrieve Store Id
     *
     * @return int
     */
    public function getStoreId()
    {
        if ($this->hasData('store_id')) {
            return $this->getData('store_id');
        }
        return Mage::app()->getStore()->getId();
    }

    /**
     * Get collection instance
     *
     * @return object
     */
    public function getResourceCollection()
    {
        $collection = parent::getResourceCollection();
        $collection->setStoreId($this->getStoreId());
        return $collection;
    }

    /**
     * Get product url model
     *
     * @return Mage_Catalog_Model_Product_Url
     */
    public function getUrlModel()
    {
        if ($this->_urlModel === null) {
            $this->_urlModel = Mage::getSingleton('Mage_Catalog_Model_Category_Url');
        }
        return $this->_urlModel;
    }

    /**
     * Validate Product Data
     *
     * @todo implement full validation process with errors returning which are ignoring now
     *
     * @return array
     */
    public function validate()
    {
        Mage::dispatchEvent($this->_eventPrefix . '_validate_before', array($this->_eventObject => $this));
        $this->_enforceFunctionalLimitations();
        $result = $this->_getResource()->validate($this);
        Mage::dispatchEvent($this->_eventPrefix . '_validate_after', array($this->_eventObject => $this));
        return $result;
    }

    /**
     * Get product name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_getData('name');
    }

    /**
     * Get product price throught type instance
     *
     * @return unknown
     */
    public function getPrice()
    {
        if ($this->_calculatePrice || !$this->getData('price')) {
            return $this->getPriceModel()->getPrice($this);
        } else {
            return $this->getData('price');
        }
    }

    /**
     * Set Price calculation flag
     *
     * @param bool $calculate
     * @return void
     */
    public function setPriceCalculation($calculate = true)
    {
        $this->_calculatePrice = $calculate;
    }

    /**
     * Get product type identifier
     *
     * @return string
     */
    public function getTypeId()
    {
        return $this->_getData('type_id');
    }

    /**
     * Get product status
     *
     * @return int
     */
    public function getStatus()
    {
        if (is_null($this->_getData('status'))) {
            $this->setData('status', Mage_Catalog_Model_Category_Status::STATUS_ENABLED);
        }
        return $this->_getData('status');
    }

    /**
     * Retrieve type instance of the product.
     * Type instance implements product type depended logic and is a singleton shared by all products of the same type.
     *
     * @return Mage_Catalog_Model_Product_Type_Abstract
     */
    public function getTypeInstance()
    {
        if ($this->_typeInstance === null) {
            $this->_typeInstance = Mage::getSingleton('Mage_Catalog_Model_Category_Type')
            ->factory($this);
        }
        return $this->_typeInstance;
    }


    /**
     * Set type instance for the product
     *
     * @param Mage_Catalog_Model_Product_Type_Abstract|null $instance  Product type instance
     * @return Mage_Catalog_Model_Product
     */
    public function setTypeInstance($instance)
    {
        $this->_typeInstance = $instance;
        return $this;
    }

    /**
     * Retrieve link instance
     *
     * @return  Mage_Catalog_Model_Product_Link
     */
    public function getLinkInstance()
    {
        if (!$this->_linkInstance) {
            $this->_linkInstance = Mage::getSingleton('Mage_Catalog_Model_Category_Link');
        }
        return $this->_linkInstance;
    }

    /**
     * Retrieve product websites identifiers
     *
     * @return array
     */
    public function getWebsiteIds()
    {
        if (!$this->hasWebsiteIds()) {
            $ids = $this->_getResource()->getWebsiteIds($this);
            $this->setWebsiteIds($ids);
        }
        return $this->getData('website_ids');
    }

    /**
     * Get all sore ids where product is presented
     *
     * @return array
     */
    public function getStoreIds()
    {
        if (!$this->hasStoreIds()) {
            $storeIds = array();
            if ($websiteIds = $this->getWebsiteIds()) {
                foreach ($websiteIds as $websiteId) {
                    $websiteStores = Mage::app()->getWebsite($websiteId)->getStoreIds();
                    $storeIds = array_merge($storeIds, $websiteStores);
                }
            }
            $this->setStoreIds($storeIds);
        }
        return $this->getData('store_ids');
    }

    /**
     * Retrieve product attributes
     * if $groupId is null - retrieve all product attributes
     *
     * @param int  $groupId   Retrieve attributes of the specified group
     * @param bool $skipSuper Not used
     * @return array
     * TODO
     */
    public function getAttributes($groupId = null, $skipSuper = false)
    {
        $productAttributes = $this->getTypeInstance()->getEditableAttributes($this);
        if ($groupId) {
            $attributes = array();
            foreach ($productAttributes as $attribute) {
                if ($attribute->isInGroup($this->getAttributeSetId(), $groupId)) {
                    $attributes[] = $attribute;
                }
            }
        } else {
            $attributes = $productAttributes;
        }

        return $attributes;
    }


    /**
     * Check product options and type options and save them, too
     */
    protected function _beforeSave()
    {
        $this->cleanCache();
        $this->setTypeHasOptions(false);
        $this->setTypeHasRequiredOptions(false);

        $this->getTypeInstance()->beforeSave($this);

        $hasOptions         = false;
        $hasRequiredOptions = false;

        /**
         * $this->_canAffectOptions - set by type instance only
         * $this->getCanSaveCustomOptions() - set either in controller when "Custom Options" ajax tab is loaded,
         * or in type instance as well
         */
        $this->canAffectOptions($this->_canAffectOptions && $this->getCanSaveCustomOptions());
        if ($this->getCanSaveCustomOptions()) {
            $options = $this->getProductOptions();
            if (is_array($options)) {
                $this->setIsCustomOptionChanged(true);
                foreach ($this->getProductOptions() as $option) {
                    $this->getOptionInstance()->addOption($option);
                    if ((!isset($option['is_delete'])) || $option['is_delete'] != '1') {
                        $hasOptions = true;
                    }
                }
                foreach ($this->getOptionInstance()->getOptions() as $option) {
                    if ($option['is_require'] == '1') {
                        $hasRequiredOptions = true;
                        break;
                    }
                }
            }
        }

        /**
         * Set true, if any
         * Set false, ONLY if options have been affected by Options tab and Type instance tab
         */
        if ($hasOptions || (bool)$this->getTypeHasOptions()) {
            $this->setHasOptions(true);
            if ($hasRequiredOptions || (bool)$this->getTypeHasRequiredOptions()) {
                $this->setRequiredOptions(true);
            } elseif ($this->canAffectOptions()) {
                $this->setRequiredOptions(false);
            }
        } elseif ($this->canAffectOptions()) {
            $this->setHasOptions(false);
            $this->setRequiredOptions(false);
        }

        parent::_beforeSave();
        $this->_enforceFunctionalLimitations();
    }

    /**
     * Sub-routine for enforcing functional limitations
     *
     * @throws Mage_Core_Exception
     */
    protected function _enforceFunctionalLimitations()
    {
        /** @var $limitation Mage_Catalog_Model_Product_Limitation */
        $limitation = Mage::getObjectManager()->get('Mage_Catalog_Model_Product_Limitation');
        if ($this->isObjectNew() && $limitation->isCreateRestricted()) {
            throw new Mage_Core_Exception($limitation->getCreateRestrictedMessage());
        }
    }

    /**
     * Check/set if options can be affected when saving product
     * If value specified, it will be set.
     *
     * @param   bool $value
     * @return  bool
     */
    public function canAffectOptions($value = null)
    {
        if (null !== $value) {
            $this->_canAffectOptions = (bool)$value;
        }
        return $this->_canAffectOptions;
    }

    /**
     * Saving product type related data and init index
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _afterSave()
    {
        $this->getLinkInstance()->saveProductRelations($this);
        $this->getTypeInstance()->save($this);

        /**
         * Product Options
         */
        $this->getOptionInstance()->setProduct($this)
        ->saveOptions();

        $result = parent::_afterSave();

        Mage::getSingleton('Mage_Index_Model_Indexer')->processEntityAction(
                $this, self::ENTITY, Mage_Index_Model_Event::TYPE_SAVE
        );
        return $result;
    }

    /**
     * Clear chache related with product and protect delete from not admin
     * Register indexing event before delete product
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _beforeDelete()
    {
        $this->_protectFromNonAdmin();
        $this->cleanCache();
        Mage::getSingleton('Mage_Index_Model_Indexer')->logEvent(
                $this, self::ENTITY, Mage_Index_Model_Event::TYPE_DELETE
        );
        return parent::_beforeDelete();
    }

    /**
     * Init indexing process after product delete commit
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _afterDeleteCommit()
    {
        parent::_afterDeleteCommit();
        Mage::getSingleton('Mage_Index_Model_Indexer')->indexEvents(
                self::ENTITY, Mage_Index_Model_Event::TYPE_DELETE
        );
    }

    /**
     * Load product options if they exists
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _afterLoad()
    {
        parent::_afterLoad();
        /**
         * Load product options
         */
        if ($this->getHasOptions()) {
            foreach ($this->getProductOptionsCollection() as $option) {
                $option->setProduct($this);
                $this->addOption($option);
            }
        }
        return $this;
    }


    /**
     * Clear cache related with product id
     *
     * @return Mage_Catalog_Model_Product
     */
    public function cleanCache()
    {
        Mage::app()->cleanCache('catalog_product_'.$this->getId());
        return $this;
    }


    /*******************************************************************************
     ** Media API
    */
    /**
     * Retrive attributes for media gallery
     *
     * @return array
     */
    public function getMediaAttributes()
    {
        if (!$this->hasMediaAttributes()) {
            $mediaAttributes = array();
            foreach ($this->getAttributes() as $attribute) {
                if($attribute->getFrontend()->getInputType() == 'media_image') {
                    $mediaAttributes[$attribute->getAttributeCode()] = $attribute;
                }
            }
            $this->setMediaAttributes($mediaAttributes);
        }
        return $this->getData('media_attributes');
    }

    /**
     * Retrive media gallery images
     *
     * @return Varien_Data_Collection
     */
    public function getMediaGalleryImages()
    {
        if(!$this->hasData('media_gallery_images') && is_array($this->getMediaGallery('images'))) {
            $images = new Varien_Data_Collection();
            foreach ($this->getMediaGallery('images') as $image) {
                if (isset($image['disabled']) && $image['disabled']) {
                    continue;
                }
                $image['url'] = $this->getMediaConfig()->getMediaUrl($image['file']);
                $image['id'] = isset($image['value_id']) ? $image['value_id'] : null;
                $image['path'] = $this->getMediaConfig()->getMediaPath($image['file']);
                $images->addItem(new Varien_Object($image));
            }
            $this->setData('media_gallery_images', $images);
        }

        return $this->getData('media_gallery_images');
    }

    /**
     * Add image to media gallery
     *
     * @param string        $file              file path of image in file system
     * @param string|array  $mediaAttribute    code of attribute with type 'media_image',
     *                                          leave blank if image should be only in gallery
     * @param boolean       $move              if true, it will move source file
     * @param boolean       $exclude           mark image as disabled in product page view
     * @return Mage_Catalog_Model_Product
     */
    public function addImageToMediaGallery($file, $mediaAttribute=null, $move=false, $exclude=true)
    {
        $attributes = $this->getTypeInstance()->getSetAttributes($this);
        if (!isset($attributes['media_gallery'])) {
            return $this;
        }
        $mediaGalleryAttribute = $attributes['media_gallery'];
        /* @var $mediaGalleryAttribute Mage_Catalog_Model_Resource_Eav_Attribute */
        $mediaGalleryAttribute->getBackend()->addImage($this, $file, $mediaAttribute, $move, $exclude);
        return $this;
    }

    /**
     * Retrive product media config
     *
     * @return Mage_Catalog_Model_Product_Media_Config
     */
    public function getMediaConfig()
    {
        return Mage::getSingleton('Mage_Catalog_Model_Product_Media_Config');
    }

    /**
     * Create duplicate
     *
     * @return Mage_Catalog_Model_Product
     */
    public function duplicate()
    {
        $this->getWebsiteIds();
        $this->getCategoryIds();

        /* @var $newProduct Mage_Catalog_Model_Product */
        $newProduct = Mage::getModel('Mage_Catalog_Model_Product')->setData($this->getData())
        ->setIsDuplicate(true)
        ->setOriginalId($this->getId())
        ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
        ->setCreatedAt(null)
        ->setUpdatedAt(null)
        ->setId(null)
        ->setStoreId(Mage::app()->getStore()->getId());

        Mage::dispatchEvent(
                'catalog_model_product_duplicate',
                array('current_product' => $this, 'new_product' => $newProduct)
        );

        /* Prepare Related*/
        $data = array();
        $this->getLinkInstance()->useRelatedLinks();
        $attributes = array();
        foreach ($this->getLinkInstance()->getAttributes() as $_attribute) {
            if (isset($_attribute['code'])) {
                $attributes[] = $_attribute['code'];
            }
        }
        foreach ($this->getRelatedLinkCollection() as $_link) {
            $data[$_link->getLinkedProductId()] = $_link->toArray($attributes);
        }
        $newProduct->setRelatedLinkData($data);

        /* Prepare UpSell*/
        $data = array();
        $this->getLinkInstance()->useUpSellLinks();
        $attributes = array();
        foreach ($this->getLinkInstance()->getAttributes() as $_attribute) {
            if (isset($_attribute['code'])) {
                $attributes[] = $_attribute['code'];
            }
        }
        foreach ($this->getUpSellLinkCollection() as $_link) {
            $data[$_link->getLinkedProductId()] = $_link->toArray($attributes);
        }
        $newProduct->setUpSellLinkData($data);

        /* Prepare Cross Sell */
        $data = array();
        $this->getLinkInstance()->useCrossSellLinks();
        $attributes = array();
        foreach ($this->getLinkInstance()->getAttributes() as $_attribute) {
            if (isset($_attribute['code'])) {
                $attributes[] = $_attribute['code'];
            }
        }
        foreach ($this->getCrossSellLinkCollection() as $_link) {
            $data[$_link->getLinkedProductId()] = $_link->toArray($attributes);
        }
        $newProduct->setCrossSellLinkData($data);

        /* Prepare Grouped */
        $data = array();
        $this->getLinkInstance()->useGroupedLinks();
        $attributes = array();
        foreach ($this->getLinkInstance()->getAttributes() as $_attribute) {
            if (isset($_attribute['code'])) {
                $attributes[] = $_attribute['code'];
            }
        }
        foreach ($this->getGroupedLinkCollection() as $_link) {
            $data[$_link->getLinkedProductId()] = $_link->toArray($attributes);
        }
        $newProduct->setGroupedLinkData($data);

        $newProduct->save();

        $this->getOptionInstance()->duplicate($this->getId(), $newProduct->getId());
        $this->getResource()->duplicate($this->getId(), $newProduct->getId());

        // TODO - duplicate product on all stores of the websites it is associated with
        /*if ($storeIds = $this->getWebsiteIds()) {
         foreach ($storeIds as $storeId) {
        $this->setStoreId($storeId)
        ->load($this->getId());

        $newProduct->setData($this->getData())
        ->setSku(null)
        ->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED)
        ->setId($newId)
        ->save();
        }
        }*/
        return $newProduct;
    }

    /**
     * Is product grouped
     *
     * @return bool
     */
    public function isSuperGroup()
    {
        return $this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED;
    }

    /**
     * Alias for isConfigurable()
     *
     * @return bool
     */
    public function isSuperConfig()
    {
        return $this->isConfigurable();
    }
    /**
     * Check is product grouped
     *
     * @return bool
     */
    public function isGrouped()
    {
        return $this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED;
    }

    /**
     * Check is product configurable
     *
     * @return bool
     */
    public function isConfigurable()
    {
        return $this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE;
    }

    /**
     * Whether product configurable or grouped
     *
     * @return bool
     */
    public function isSuper()
    {
        return $this->isConfigurable() || $this->isGrouped();
    }

    /**
     * Returns visible status IDs in catalog
     *
     * @return array
     */
    public function getVisibleInCatalogStatuses()
    {
        return Mage::getSingleton('Mage_Catalog_Model_Product_Status')->getVisibleStatusIds();
    }

    /**
     * Retrieve visible statuses
     *
     * @return array
     */
    public function getVisibleStatuses()
    {
        return Mage::getSingleton('Mage_Catalog_Model_Product_Status')->getVisibleStatusIds();
    }

    /**
     * Check Product visilbe in catalog
     *
     * @return bool
     */
    public function isVisibleInCatalog()
    {
        return in_array($this->getStatus(), $this->getVisibleInCatalogStatuses());
    }

    /**
     * Retrieve visible in site visibilities
     *
     * @return array
     */
    public function getVisibleInSiteVisibilities()
    {
        return Mage::getSingleton('Mage_Catalog_Model_Product_Visibility')->getVisibleInSiteIds();
    }

    /**
     * Check Product visible in site
     *
     * @return bool
     */
    public function isVisibleInSiteVisibility()
    {
        return in_array($this->getVisibility(), $this->getVisibleInSiteVisibilities());
    }

    /**
     * Checks product can be duplicated
     *
     * @return boolean
     */
    public function isDuplicable()
    {
        return $this->_isDuplicable;
    }

    /**
     * Set is duplicable flag
     *
     * @param boolean $value
     * @return Mage_Catalog_Model_Product
     */
    public function setIsDuplicable($value)
    {
        $this->_isDuplicable = (boolean) $value;
        return $this;
    }


    /**
     * Check is product available for sale
     *
     * @return bool
     */
    public function isSalable()
    {
        Mage::dispatchEvent('catalog_product_is_salable_before', array(
                'product'   => $this
        ));

        $salable = $this->isAvailable();

        $object = new Varien_Object(array(
                'product'    => $this,
                'is_salable' => $salable
        ));
        Mage::dispatchEvent('catalog_product_is_salable_after', array(
                'product'   => $this,
                'salable'   => $object
        ));
        return $object->getIsSalable();
    }

    /**
     * Check whether the product type or stock allows to purchase the product
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->getTypeInstance()->isSalable($this)
        || Mage::helper('Mage_Catalog_Helper_Product')->getSkipSaleableCheck();
    }

    /**
     * Is product salable detecting by product type
     *
     * @return bool
     */
    public function getIsSalable()
    {
        $productType = $this->getTypeInstance();
        if (method_exists($productType, 'getIsSalable')) {
            return $productType->getIsSalable($this);
        }
        if ($this->hasData('is_salable')) {
            return $this->getData('is_salable');
        }

        return $this->isSalable();
    }

    /**
     * Check is a virtual product
     * Data helper wrapper
     *
     * @return bool
     */
    public function isVirtual()
    {
        return $this->getIsVirtual();
    }

    /**
     * Whether the product is a recurring payment
     *
     * @return bool
     */
    public function isRecurring()
    {
        return $this->getIsRecurring() == '1';
    }

    /**
     * Alias for isSalable()
     *
     * @return bool
     */
    public function isSaleable()
    {
        return $this->isSalable();
    }

    /**
     * Whether product available in stock
     *
     * @return bool
     */
    public function isInStock()
    {
        return $this->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
    }

    /**
     * Get attribute text by its code
     *
     * @param $attributeCode Code of the attribute
     * @return string
     */
    public function getAttributeText($attributeCode)
    {
        return $this->getResource()
        ->getAttribute($attributeCode)
        ->getSource()
        ->getOptionText($this->getData($attributeCode));
    }

    /**
     * Returns array with dates for custom design
     *
     * @return array
     */
    public function getCustomDesignDate()
    {
        $result = array();
        $result['from'] = $this->getData('custom_design_from');
        $result['to'] = $this->getData('custom_design_to');

        return $result;
    }

    /**
     * Retrieve Product URL
     *
     * @param  bool $useSid
     * @return string
     */
    public function getProductUrl($useSid = null)
    {
        return $this->getUrlModel()->getProductUrl($this, $useSid);
    }

    /**
     * Retrieve URL in current store
     *
     * @param array $params the route params
     * @return string
     */
    public function getUrlInStore($params = array())
    {
        return $this->getUrlModel()->getUrlInStore($this, $params);
    }

    /**
     * Formats URL key
     *
     * @param $str URL
     * @return string
     */
    public function formatUrlKey($str)
    {
        return $this->getUrlModel()->formatUrlKey($str);
    }

    /**
     * Retrieve Product Url Path (include category)
     *
     * @param Mage_Catalog_Model_Category $category
     * @return string
     */
    public function getUrlPath($category=null)
    {
        return $this->getUrlModel()->getUrlPath($this, $category);
    }

    /**
     * Save current attribute with code $code and assign new value
     *
     * @param string $code  Attribute code
     * @param mixed  $value New attribute value
     * @param int    $store Store ID
     * @return void
     */
    public function addAttributeUpdate($code, $value, $store)
    {
        $oldValue = $this->getData($code);
        $oldStore = $this->getStoreId();

        $this->setData($code, $value);
        $this->setStoreId($store);
        $this->getResource()->saveAttribute($this, $code);

        $this->setData($code, $oldValue);
        $this->setStoreId($oldStore);
    }

    /**
     * Renders the object to array
     *
     * @param array $arrAttributes Attribute array
     * @return array
     */
    public function toArray(array $arrAttributes=array())
    {
        $data = parent::toArray($arrAttributes);
        if ($stock = $this->getStockItem()) {
            $data['stock_item'] = $stock->toArray();
        }
        unset($data['stock_item']['product']);
        return $data;
    }

    /**
     * Same as setData(), but also initiates the stock item (if it is there)
     *
     * @param array $data Array to form the object from
     * @return Mage_Catalog_Model_Product
     */
    public function fromArray($data)
    {
        if (isset($data['stock_item'])) {
            if (Mage::helper('Mage_Catalog_Helper_Data')->isModuleEnabled('Mage_CatalogInventory')) {
                $stockItem = Mage::getModel('Mage_CatalogInventory_Model_Stock_Item')
                ->setData($data['stock_item'])
                ->setProduct($this);
                $this->setStockItem($stockItem);
            }
            unset($data['stock_item']);
        }
        $this->setData($data);
        return $this;
    }

    /**
     * Delete product
     *
     * @return Mage_Catalog_Model_Product
     */
    public function delete()
    {
        parent::delete();
        Mage::dispatchEvent($this->_eventPrefix.'_delete_after_done', array($this->_eventObject=>$this));
        return $this;
    }

    /**
     * Returns request path
     *
     * @return string
     */
    public function getRequestPath()
    {
        return $this->_getData('request_path');
    }

    /**
     * Custom function for other modules
     * @return string
     */

    public function getGiftMessageAvailable()
    {
        return $this->_getData('gift_message_available');
    }

    /**
     * Returns rating summary
     *
     * @return mixed
     */
    public function getRatingSummary()
    {
        return $this->_getData('rating_summary');
    }

    /**
     * Check is product composite
     *
     * @return bool
     */
    public function isComposite()
    {
        return $this->getTypeInstance()->isComposite($this);
    }

    /**
     * Check if product can be configured
     *
     * @return bool
     */
    public function canConfigure()
    {
        $options = $this->getOptions();
        return !empty($options) || $this->getTypeInstance()->canConfigure($this);
    }

    /**
     * Retrieve sku through type instance
     *
     * @return string
     */
    public function getSku()
    {
        return $this->getTypeInstance()->getSku($this);
    }

    /**
     * Retrieve weight throught type instance
     *
     * @return unknown
     */
    public function getWeight()
    {
        return $this->getTypeInstance()->getWeight($this);
    }

    /**
     * Retrieve option instance
     *
     * @return Mage_Catalog_Model_Product_Option
     */
    public function getOptionInstance()
    {
        if (!$this->_optionInstance) {
            $this->_optionInstance = Mage::getSingleton('Mage_Catalog_Model_Product_Option');
        }
        return $this->_optionInstance;
    }

    /**
     * Retrieve options collection of product
     *
     * @return Mage_Catalog_Model_Resource_Product_Option_Collection
     */
    public function getProductOptionsCollection()
    {
        $collection = $this->getOptionInstance()
        ->getProductOptionCollection($this);

        return $collection;
    }

    /**
     * Add option to array of product options
     *
     * @param Mage_Catalog_Model_Product_Option $option
     * @return Mage_Catalog_Model_Product
     */
    public function addOption(Mage_Catalog_Model_Product_Option $option)
    {
        $this->_options[$option->getId()] = $option;
        return $this;
    }

    /**
     * Get option from options array of product by given option id
     *
     * @param int $optionId
     * @return Mage_Catalog_Model_Product_Option | null
     */
    public function getOptionById($optionId)
    {
        if (isset($this->_options[$optionId])) {
            return $this->_options[$optionId];
        }

        return null;
    }

    /**
     * Get all options of product
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Retrieve is a virtual product
     *
     * @return bool
     */
    public function getIsVirtual()
    {
        return $this->getTypeInstance()->isVirtual($this);
    }

    /**
     * Add custom option information to product
     *
     * @param   string $code    Option code
     * @param   mixed  $value   Value of the option
     * @param   int    $product Product ID
     * @return  Mage_Catalog_Model_Product
     */
    public function addCustomOption($code, $value, $product=null)
    {
        $product = $product ? $product : $this;
        $option = Mage::getModel('Mage_Catalog_Model_Product_Configuration_Item_Option')
        ->addData(array(
                'product_id'=> $product->getId(),
                'product'   => $product,
                'code'      => $code,
                'value'     => $value,
        ));
        $this->_customOptions[$code] = $option;
        return $this;
    }

    /**
     * Sets custom options for the product
     *
     * @param array $options Array of options
     * @return void
     */
    public function setCustomOptions(array $options)
    {
        $this->_customOptions = $options;
    }

    /**
     * Get all custom options of the product
     *
     * @return array
     */
    public function getCustomOptions()
    {
        return $this->_customOptions;
    }

    /**
     * Get product custom option info
     *
     * @param   string $code
     * @return  array
     */
    public function getCustomOption($code)
    {
        if (isset($this->_customOptions[$code])) {
            return $this->_customOptions[$code];
        }
        return null;
    }

    /**
     * Checks if there custom option for this product
     *
     * @return bool
     */
    public function hasCustomOptions()
    {
        if (count($this->_customOptions)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check availability display product in category
     *
     * @param   int $categoryId
     * @return  bool
     */
    public function canBeShowInCategory($categoryId)
    {
        return $this->_getResource()->canBeShowInCategory($this, $categoryId);
    }

    /**
     * Retrieve category ids where product is available
     *
     * @return array
     */
    public function getAvailableInCategories()
    {
        return $this->_getResource()->getAvailableInCategories($this);
    }

    /**
     * Retrieve default attribute set id
     *
     * @return int
     */
    public function getDefaultAttributeSetId()
    {
        return $this->getResource()->getEntityType()->getDefaultAttributeSetId();
    }

    /**
     * Return Catalog Product Image helper instance
     *
     * @return Mage_Catalog_Helper_Image
     */
    protected function _getImageHelper()
    {
        return Mage::helper('Mage_Catalog_Helper_Image');
    }

    /**
     *  Returns system reserved attribute codes
     *
     *  @return array Reserved attribute names
     */
    public function getReservedAttributes()
    {
        if ($this->_reservedAttributes === null) {
            $_reserved = array('position');
            $methods = get_class_methods(__CLASS__);
            foreach ($methods as $method) {
                if (preg_match('/^get([A-Z]{1}.+)/', $method, $matches)) {
                    $method = $matches[1];
                    $tmp = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $method));
                    $_reserved[] = $tmp;
                }
            }
            $_allowed = array(
                    'type_id','calculated_final_price','request_path','rating_summary'
            );
            $this->_reservedAttributes = array_diff($_reserved, $_allowed);
        }
        return $this->_reservedAttributes;
    }

    /**
     *  Check whether attribute reserved or not
     *
     *  @param Mage_Catalog_Model_Entity_Attribute $attribute Attribute model object
     *  @return boolean
     */
    public function isReservedAttribute ($attribute)
    {
        return $attribute->getIsUserDefined()
        && in_array($attribute->getAttributeCode(), $this->getReservedAttributes());
    }

    /**
     * Set original loaded data if needed
     *
     * @param string $key
     * @param mixed $data
     * @return Varien_Object
     */
    public function setOrigData($key=null, $data=null)
    {
        if (Mage::app()->getStore()->isAdmin()) {
            return parent::setOrigData($key, $data);
        }

        return $this;
    }

    /**
     * Reset all model data
     *
     * @return Mage_Catalog_Model_Product
     */
    public function reset()
    {
        $this->unlockAttributes();
        $this->_clearData();
        return $this;
    }

    /**
     * Get cahce tags associated with object id
     *
     * @return array
     */
    public function getCacheIdTags()
    {
        $tags = parent::getCacheIdTags();
        $affectedCategoryIds = $this->getAffectedCategoryIds();
        if (!$affectedCategoryIds) {
            $affectedCategoryIds = $this->getCategoryIds();
        }
        foreach ($affectedCategoryIds as $categoryId) {
            $tags[] = Mage_Catalog_Model_Category::CACHE_TAG.'_'.$categoryId;
        }
        return $tags;
    }

    /**
     * Check for empty SKU on each product
     *
     * @param  array $productIds
     * @return boolean|null
     */
    public function isProductsHasSku(array $productIds)
    {
        $products = $this->_getResource()->getProductsSku($productIds);
        if (count($products)) {
            foreach ($products as $product) {
                if (!strlen($product['sku'])) {
                    return false;
                }
            }
            return true;
        }
        return null;
    }

    /**
     * Parse buyRequest into options values used by product
     *
     * @param  Varien_Object $buyRequest
     * @return Varien_Object
     */
    public function processBuyRequest(Varien_Object $buyRequest)
    {
        $options = new Varien_Object();

        /* add product custom options data */
        $customOptions = $buyRequest->getOptions();
        if (is_array($customOptions)) {
            $options->setOptions(array_diff($buyRequest->getOptions(), array('')));
        }

        /* add product type selected options data */
        $type = $this->getTypeInstance();
        $typeSpecificOptions = $type->processBuyRequest($this, $buyRequest);
        $options->addData($typeSpecificOptions);

        /* check correctness of product's options */
        $options->setErrors($type->checkProductConfiguration($this, $buyRequest));

        return $options;
    }

    /**
     * Get preconfigured values from product
     *
     * @return Varien_Object
     */
    public function getPreconfiguredValues()
    {
        $preconfiguredValues = $this->getData('preconfigured_values');
        if (!$preconfiguredValues) {
            $preconfiguredValues = new Varien_Object();
        }

        return $preconfiguredValues;
    }

    /**
     * Prepare product custom options.
     * To be sure that all product custom options does not has ID and has product instance
     *
     * @return Mage_Catalog_Model_Product
     */
    public function prepareCustomOptions()
    {
        foreach ($this->getCustomOptions() as $option) {
            if (!is_object($option->getProduct()) || $option->getId()) {
                $this->addCustomOption($option->getCode(), $option->getValue());
            }
        }

        return $this;
    }

    /**
     * Clearing references on product
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _clearReferences()
    {
        $this->_clearOptionReferences();
        return $this;
    }

    /**
     * Clearing product's data
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _clearData()
    {
        foreach ($this->_data as $data){
            if (is_object($data) && method_exists($data, 'reset')){
                $data->reset();
            }
        }

        $this->setData(array());
        $this->setOrigData();
        $this->_customOptions       = array();
        $this->_optionInstance      = null;
        $this->_options             = array();
        $this->_canAffectOptions    = false;
        $this->_errors              = array();

        return $this;
    }

    /**
     * Clearing references to product from product's options
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _clearOptionReferences()
    {
        /**
         * unload product options
         */
        if (!empty($this->_options)) {
            foreach ($this->_options as $key => $option) {
                $option->setProduct();
                $option->clearInstance();
            }
        }

        return $this;
    }

    /**
     * Retrieve product entities info as array
     *
     * @param string|array $columns One or several columns
     * @return array
     */
    public function getProductEntitiesInfo($columns = null)
    {
        return $this->_getResource()->getProductEntitiesInfo($columns);
    }

    /**
     * Checks whether product has disabled status
     *
     * @return bool
     */
    public function isDisabled()
    {
        return $this->getStatus() == Mage_Catalog_Model_Product_Status::STATUS_DISABLED;
    }

    /**
     * Sets product image from it's child if possible
     *
     * @return string
     */
    public function getImage()
    {
        $this->getTypeInstance()->setImageFromChildProduct($this);
        return parent::getImage();
    }


}