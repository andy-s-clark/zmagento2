<?php
/**
* Read-Only query class for Build Web Services
* TODO This should be an abstract model rather than a helper
*/
class Buildcom_Webserv_Helper_Data //extends Mage_Core_Helper_Abstract
{
	const PRODUCTION_URI = 'http://webservices.sys.id.build.com:8080/build-webservices-1.0.0/';
	const DEV_URI = 'http://devbox2.build.internal:8080/build-webservices-1.0.0/services';
	protected $services_uri;
	protected $service;
	private $parameters = array();

	/**
	 *
	 * @param string $service
	 * @param string $production
	*/
	function __construct($service = NULL, $production = FALSE) {
		$this->services_uri = $production ? self::PRODUCTION_URI : self::DEV_URI;
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