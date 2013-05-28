<?php
/**
 * Read-Only query class for Build Web Services
 * Just for testing...
 */
class Buildcom_Webserv_Helper_Direct extends Mage_Core_Helper_Abstract
{
    const XML_NODE_SERVER_PRODUCTION = 'default/webserv/server/production';
    const XML_NODE_SERVER_DEVELOPEMENT = 'default/webserv/server/development';
    const XML_NODE_SERVER_ACTIVE = 'default/webserv/server/active';

	protected $services_uri;
	protected $service;
	private $parameters = array();

	/**
	 *
	 * @param string $service
	 * @param string $production
	*/
	function __construct($service = NULL) {
        $config = Mage::getConfig();
        $server_node = $config->getNode(self::XML_NODE_SERVER_ACTIVE) == 'production' ? self::XML_NODE_SERVER_PRODUCTION : self::XML_NODE_SERVER_DEVELOPEMENT;
        $this->services_uri = $config->getNode($server_node);
        if ( ! empty($service) ) {
            $this->setService($service);
        }
	}

	/**
	 *
	 * @param string $service
	 */
	function setService($service) {
		$this->service = $service;
		$this->parameters = array();
	}

	/**
	 *
	 * @param string $attribute
	 * @param string $value
	 */
	function __set($attribute, $value) {
		$this->parameters[$attribute] = $value;
	}

	/**
	 *
	 * @param string $attribute
	 * @return string:|boolean
	 */
	function __get($attribute) {
		if ( isset($this->parameters[$attribute]) ) {
			return $this->parameters[$attribute];
		} else {
			return FALSE;
		}
	}

	/**
	 * Execute query
	 * @return boolean|object
	 */
	function execute() {
		$query_args = array();
		foreach ( $this->parameters as $key => $value ) {
			if ( is_bool($value) ) {
				$value = (int)$value;
			}
			$query_args[] = rawurlencode($key) . '=' . rawurlencode((string)$value);
		}
		$request_uri = $this->services_uri . '/' . $this->service . ( sizeof($query_args) > 0 ? '?' . implode('&', $query_args) : '');
		try {
			@$json_results = file_get_contents($request_uri);
			return json_decode($json_results);
		} catch (Exception $e) {
			return FALSE;
		}
	}
}