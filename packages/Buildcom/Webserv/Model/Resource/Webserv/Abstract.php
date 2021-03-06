<?php
abstract class Buildcom_Webserv_Model_Resource_Webserv_Abstract extends Mage_Core_Model_Resource_Abstract
{
    const XML_NODE_SERVER_PRODUCTION = 'default/webserv/server/production';
    const XML_NODE_SERVER_DEVELOPEMENT = 'default/webserv/server/development';
    const XML_NODE_SERVER_ACTIVE = 'default/webserv/server/active';

    protected $_services_uri;

    /**
     * Serializable fields declaration
     * Structure: array(
     *     <field_name> => array(
     *         <default_value_for_serialization>,
     *         <default_for_unserialization>,
     *         <whether_to_unset_empty_when serializing> // optional parameter
     *     ),
     * )
     *
     * @var array
     */
    protected $_serializableFields   = array();

    protected $_service;

    /**
     * Cached resources singleton
     *
     * @var Mage_Core_Model_Resource
     */
    protected $_resources;

    /**
     * Prefix for resources that will be used in this resource model
     *
     * @var string
     */
    protected $_resourcePrefix = 'core';

    /**
     * Connections cache for this resource model
     *
     * @var array
     */
    protected $_connections          = array();

    /**
     * Resource model name that contains entities (names of tables)
     *
     * @var string
     */
    protected $_resourceModel;

    /**
     * Tables used in this resource model
     *
     * @var array
     */
    protected $_tables               = array();

    /**
     * Main table name
     *
     * @var string
     */
    protected $_mainTable = 'test_product';

    /**
     * Main table primary key field name
     *
     * @var string
     */
    protected $_idFieldName;

    /**
     * Primery key auto increment flag
     *
     * @var bool
     */
    protected $_isPkAutoIncrement    = true;

    /**
     * Use is object new method for save of object
     *
     * @var boolean
     */
    protected $_useIsObjectNew       = false;

    /**
     * Fields of main table
     *
     * @var array
     */
    protected $_mainTableFields;

    /**
     * Main table unique keys field names
     * could array(
     *   array('field' => 'db_field_name1', 'title' => 'Field 1 should be unique')
     *   array('field' => 'db_field_name2', 'title' => 'Field 2 should be unique')
     *   array(
     *      'field' => array('db_field_name3', 'db_field_name3'),
     *      'title' => 'Field 3 and Field 4 combination should be unique'
     *   )
     * )
     * or string 'my_field_name' - will be autoconverted to
     *      array( array( 'field' => 'my_field_name', 'title' => 'my_field_name' ) )
     *
     * @var array
     */
    protected $_uniqueFields         = null;

    abstract protected function _getRequestUri($unique_id);

    abstract protected function _mapData($data);

    /**
     * Class constructor
     *
     * @param Mage_Core_Model_Resource $resource
     */
    public function __construct(Mage_Core_Model_Resource $resource)
    {
        $this->_resources = $resource;
        parent::__construct();
    }

    /**
     * Get primary key field name
     *
     * @return string
     */
    public function getIdFieldName()
    {
        return FALSE;
        if (empty($this->_idFieldName)) {
            Mage::throwException(Mage::helper('Mage_Core_Helper_Data')->__('Empty identifier field name'));
        }
        return $this->_idFieldName;
    }

    /**
     * Execute get request
     * @param string $request_uri
     */
    protected function _get_request($request_uri) {
        try {
            @$json_results = file_get_contents($request_uri);
            return json_decode($json_results);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Load an object
     *
     * @param Mage_Core_Model_Abstract $object
     * @param mixed $value
     * @param string $field field to load by (defaults to model id)
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    public function load(Mage_Core_Model_Abstract $object, $value, $field = null)
    {
        $request_uri = $this->_getRequestUri($value);

        $result = $this->_get_request($request_uri);
        $data = $this->_mapData($result);

        if ($data) {
            $object->setData($data);
        }

        $this->unserializeFields($object);
        $this->_afterLoad($object);

        return $this;
    }

    /**
     * Standard resource model initialization
     *
     * @param string $mainTable
     * @param string $idFieldName
     * @return Mage_Core_Model_Resource_Abstract
     */
    protected function _init($service)
    {
        $this->_service = $service;

        $config = Mage::getConfig();
        $server_node = $config->getNode(self::XML_NODE_SERVER_ACTIVE) == 'production' ? self::XML_NODE_SERVER_PRODUCTION : self::XML_NODE_SERVER_DEVELOPEMENT;
        $this->_services_uri = $config->getNode($server_node);
    }



    /**
     * Provide variables to serialize
     *
     * @return array
     */
    public function __sleep()
    {
        $properties = array_keys(get_object_vars($this));
        if (Mage::getIsSerializable()) {
            $properties = array_diff($properties, array('_resources', '_connections'));
        }
        return $properties;
    }

    /**
     * Restore global dependencies
     */
    public function __wakeup()
    {
        if (Mage::getIsSerializable()) {
            $this->_resources = Mage::getSingleton('Mage_Core_Model_Resource');
        }
    }

    /**
     * Initialize connections and tables for this resource model
     * If one or both arguments are string, will be used as prefix
     * If $tables is null and $connections is string, $tables will be the same
     *
     * @param string|array $connections
     * @param string|array|null $tables
     * @return Mage_Core_Model_Resource_Abstract
     */
    protected function _setResource($connections, $tables = null)
    {
        if (is_array($connections)) {
            foreach ($connections as $key => $value) {
                $this->_connections[$key] = $this->_resources->getConnection($value);
            }
        } else if (is_string($connections)) {
            $this->_resourcePrefix = $connections;
        }

        if (is_null($tables) && is_string($connections)) {
            $this->_resourceModel = $this->_resourcePrefix;
        } else if (is_array($tables)) {
            foreach ($tables as $key => $value) {
                $this->_tables[$key] = $this->_resources->getTableName($value);
            }
        } else if (is_string($tables)) {
            $this->_resourceModel = $tables;
        }
        return $this;
    }

    /**
     * Set main entity table name and primary key field name
     * If field name is omitted {table_name}_id will be used
     *
     * @param string $mainTable
     * @param string|null $idFieldName
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _setMainTable($mainTable, $idFieldName = null)
    {
        $this->_mainTable = $mainTable;
        if (null === $idFieldName) {
            $idFieldName = $mainTable . '_id';
        }

        $this->_idFieldName = $idFieldName;
        return $this;
    }


    /**
     * Returns main table name - extracted from "module/table" style and
     * validated by db adapter
     *
     * @return string
     */
    public function getMainTable()
    {
        if (empty($this->_mainTable)) {
            Mage::throwException(Mage::helper('Mage_Core_Helper_Data')->__('Empty main table name'));
        }
        return $this->getTable($this->_mainTable);
    }

    /**
     * Get real table name for db table, validated by db adapter
     *
     * @param string $tableName
     * @return string
     */
    public function getTable($tableName)
    {
        if (is_array($tableName)) {
            $cacheName    = join('@', $tableName);
            list($tableName, $entitySuffix) = $tableName;
        } else {
            $cacheName    = $tableName;
            $entitySuffix = null;
        }

        if (!is_null($entitySuffix)) {
            $tableName .= '_' . $entitySuffix;
        }

        if (!isset($this->_tables[$cacheName])) {
            $this->_tables[$cacheName] = $this->_resources->getTableName($tableName);
        }
        return $this->_tables[$cacheName];
    }

    /**
     * Get connection by name or type
     *
     * @param string $connectionName
     * @return Varien_Db_Adapter_Interface|bool
     */
    protected function _getConnection($connectionName)
    {
        if (isset($this->_connections[$connectionName])) {
            return $this->_connections[$connectionName];
        }
        $connectionNameFull = ($this->_resourcePrefix ? $this->_resourcePrefix . '_' : '') . $connectionName;
        $connectionInstance = $this->_resources->getConnection($connectionNameFull);
        // cache only active connections to detect inactive ones as soon as they become active
        if ($connectionInstance) {
            $this->_connections[$connectionName] = $connectionInstance;
        }
        return $connectionInstance;
    }

    /**
     * Retrieve connection for read data
     *
     * @return Varien_Db_Adapter_Interface
     */
    protected function _getReadAdapter()
    {
        $writeAdapter = $this->_getWriteAdapter();
        if ($writeAdapter && $writeAdapter->getTransactionLevel() > 0) {
            // if transaction is started we should use write connection for reading
            return $writeAdapter;
        }
        return $this->_getConnection('read');
    }

    /**
     * Retrieve connection for write data
     *
     * @return Varien_Db_Adapter_Interface
     */
    protected function _getWriteAdapter()
    {
        return $this->_getConnection('write');
    }

    /**
     * Temporary resolving collection compatibility
     *
     * @return Varien_Db_Adapter_Interface
     */
    public function getReadConnection()
    {
        return $this->_getReadAdapter();
    }

    /**
     * Retrieve select object for load object data
     *
     * @param string $field
     * @param mixed $value
     * @param Mage_Core_Model_Abstract $object
     * @return Zend_Db_Select
     */
    protected function _getLoadSelect($field, $value, $object)
    {
        $field  = $this->_getReadAdapter()->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), $field));
        $select = $this->_getReadAdapter()->select()
        ->from($this->getMainTable())
        ->where($field . '=?', $value);
        return $select;
    }

    /**
     * Save object object data
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    public function save(Mage_Core_Model_Abstract $object)
    {
        if ($object->isDeleted()) {
            return $this->delete($object);
        }

        $this->_serializeFields($object);
        $this->_beforeSave($object);
        $this->_checkUnique($object);
        if (!is_null($object->getId()) && (!$this->_useIsObjectNew || !$object->isObjectNew())) {
            $condition = $this->_getWriteAdapter()->quoteInto($this->getIdFieldName().'=?', $object->getId());
            /**
             * Not auto increment primary key support
             */
            if ($this->_isPkAutoIncrement) {
                $data = $this->_prepareDataForSave($object);
                unset($data[$this->getIdFieldName()]);
                $this->_getWriteAdapter()->update($this->getMainTable(), $data, $condition);
            } else {
                $select = $this->_getWriteAdapter()->select()
                ->from($this->getMainTable(), array($this->getIdFieldName()))
                ->where($condition);
                if ($this->_getWriteAdapter()->fetchOne($select) !== false) {
                    $data = $this->_prepareDataForSave($object);
                    unset($data[$this->getIdFieldName()]);
                    if (!empty($data)) {
                        $this->_getWriteAdapter()->update($this->getMainTable(), $data, $condition);
                    }
                } else {
                    $this->_getWriteAdapter()->insert($this->getMainTable(), $this->_prepareDataForSave($object));
                }
            }
        } else {
            $bind = $this->_prepareDataForSave($object);
            if ($this->_isPkAutoIncrement) {
                unset($bind[$this->getIdFieldName()]);
            }
            $this->_getWriteAdapter()->insert($this->getMainTable(), $bind);

            $object->setId($this->_getWriteAdapter()->lastInsertId($this->getMainTable()));

            if ($this->_useIsObjectNew) {
                $object->isObjectNew(false);
            }
        }

        $this->unserializeFields($object);
        $this->_afterSave($object);

        return $this;
    }

    /**
     * Delete the object
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    public function delete(Mage_Core_Model_Abstract $object)
    {
        $this->_beforeDelete($object);
        $this->_getWriteAdapter()->delete(
                $this->getMainTable(),
                $this->_getWriteAdapter()->quoteInto($this->getIdFieldName() . '=?', $object->getId())
        );
        $this->_afterDelete($object);
        return $this;
    }

    /**
     * Add unique field restriction
     *
     * @param array|string $field
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    public function addUniqueField($field)
    {
        if (is_null($this->_uniqueFields)) {
            $this->_initUniqueFields();
        }
        if (is_array($this->_uniqueFields) ) {
            $this->_uniqueFields[] = $field;
        }
        return $this;
    }

    /**
     * Reset unique fields restrictions
     *
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    public function resetUniqueField()
    {
        $this->_uniqueFields = array();
        return $this;
    }

    /**
     * Unserialize serializeable object fields
     *
     * @param Mage_Core_Model_Abstract $object
     */
    public function unserializeFields(Mage_Core_Model_Abstract $object)
    {
        foreach ($this->_serializableFields as $field => $parameters) {
            list($serializeDefault, $unserializeDefault) = $parameters;
            $this->_unserializeField($object, $field, $unserializeDefault);
        }
    }

    /**
     * Initialize unique fields
     *
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _initUniqueFields()
    {
        $this->_uniqueFields = array();
        return $this;
    }

    /**
     * Get configuration of all unique fields
     *
     * @return array
     */
    public function getUniqueFields()
    {
        if (is_null($this->_uniqueFields)) {
            $this->_initUniqueFields();
        }
        return $this->_uniqueFields;
    }

    /**
     * Prepare data for save
     *
     * @param Mage_Core_Model_Abstract $object
     * @return array
     */
    protected function _prepareDataForSave(Mage_Core_Model_Abstract $object)
    {
        return $this->_prepareDataForTable($object, $this->getMainTable());
    }

    /**
     * Check that model data fields that can be saved
     * has really changed comparing with origData
     *
     * @param Mage_Core_Model_Abstract $object
     * @return boolean
     */
    public function hasDataChanged($object)
    {
        if (!$object->getOrigData()) {
            return true;
        }

        $fields = $this->_getWriteAdapter()->describeTable($this->getMainTable());
        foreach (array_keys($fields) as $field) {
            if ($object->getOrigData($field) != $object->getData($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prepare value for save
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    protected function _prepareValueForSave($value, $type)
    {
        return $this->_prepareTableValueForSave($value, $type);
    }

    /**
     * Check for unique values existence
     *
     * @param Mage_Core_Model_Abstract $object
     * @return Mage_Core_Model_Resource_Db_Abstract
     * @throws Mage_Core_Exception
     */
    protected function _checkUnique(Mage_Core_Model_Abstract $object)
    {
        $existent = array();
        $fields = $this->getUniqueFields();
        if (!empty($fields)) {
            if (!is_array($fields)) {
                $this->_uniqueFields = array(
                        array(
                                'field' => $fields,
                                'title' => $fields
                        ));
            }

            $data = new Varien_Object($this->_prepareDataForSave($object));
            $select = $this->_getWriteAdapter()->select()
            ->from($this->getMainTable());

            foreach ($fields as $unique) {
                $select->reset(Zend_Db_Select::WHERE);

                if (is_array($unique['field'])) {
                    foreach ($unique['field'] as $field) {
                        $select->where($field . '=?', trim($data->getData($field)));
                    }
                } else {
                    $select->where($unique['field'] . '=?', trim($data->getData($unique['field'])));
                }

                if ($object->getId() || $object->getId() === '0') {
                    $select->where($this->getIdFieldName() . '!=?', $object->getId());
                }

                $test = $this->_getWriteAdapter()->fetchRow($select);
                if ($test) {
                    $existent[] = $unique['title'];
                }
            }
        }

        if (!empty($existent)) {
            if (count($existent) == 1 ) {
                $error = Mage::helper('Mage_Core_Helper_Data')->__('%s already exists.', $existent[0]);
            } else {
                $error = Mage::helper('Mage_Core_Helper_Data')->__('%s already exist.', implode(', ', $existent));
            }
            Mage::throwException($error);
        }
        return $this;
    }

    /**
     * After load
     *
     * @param Mage_Core_Model_Abstract $object
     */
    public function afterLoad(Mage_Core_Model_Abstract $object)
    {
        $this->_afterLoad($object);
    }

    /**
     * Perform actions after object load
     *
     * @param Varien_Object $object
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _afterLoad(Mage_Core_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Perform actions before object save
     *
     * @param Varien_Object $object
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Perform actions after object save
     *
     * @param Varien_Object $object
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _afterSave(Mage_Core_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Perform actions before object delete
     *
     * @param Varien_Object $object
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _beforeDelete(Mage_Core_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Perform actions after object delete
     *
     * @param Varien_Object $object
     * @return Mage_Core_Model_Resource_Db_Abstract
     */
    protected function _afterDelete(Mage_Core_Model_Abstract $object)
    {
        return $this;
    }

    /**
     * Serialize serializeable fields of the object
     *
     * @param Mage_Core_Model_Abstract $object
     */
    protected function _serializeFields(Mage_Core_Model_Abstract $object)
    {
        foreach ($this->_serializableFields as $field => $parameters) {
            list($serializeDefault, $unserializeDefault) = $parameters;
            $this->_serializeField($object, $field, $serializeDefault, isset($parameters[2]));
        }
    }

    /**
     * Retrieve table checksum
     *
     * @param string|array $table
     * @return int|array
     */
    public function getChecksum($table)
    {
        if (!$this->_getReadAdapter()) {
            return false;
        }
        $checksum = $this->_getReadAdapter()->getTablesChecksum($table);
        if (count($checksum) == 1) {
            return $checksum[$table];
        }
        return $checksum;
    }
}