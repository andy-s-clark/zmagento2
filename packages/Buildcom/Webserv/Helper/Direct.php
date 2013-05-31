<?php
/**
 * Read-Only query class for Build Web Services
 * Currently just for testing...
 */
class Buildcom_Webserv_Helper_Direct extends Mage_Core_Helper_Abstract
{
    const XML_NODE_SERVER_PRODUCTION = 'default/webserv/server/production';
    const XML_NODE_SERVER_DEVELOPEMENT = 'default/webserv/server/development';
    const XML_NODE_SERVER_ACTIVE = 'default/webserv/server/active';

    protected $services_uri;
    protected $service;
    protected $query_args = array();
    protected $post_data = array();

    /**
     *
     * @param string $service
     * @param string $production
     */
    public function __construct($service = NULL) {
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
    public function setService($service) {
        $this->service = $service;
        $this->query_args = array();
        $this->post_data = array();
        return $this;
    }

    /**
     * Set a query string argument
     *
     * @param string $attribute
     * @param string $value
     */
    public function setQueryArg($attribute, $value) {
        $this->query_args[$attribute] = $value;
        return $this;
    }

    /**
     * Get a query string argument
     *
     * @param string $attribute
     */
    public function getQueryArg($attribute, $value) {
        return isset($this->query_args[$attribute]) ? $this->query_args[$attribute] : FALSE;
    }

    /**
     * Set an HTTP POST query argument
     *
     * @param string $attribute
     * @param string $value
     */
    public function setPostData($attribute, $value) {
        $this->post_data[$attribute] = $value;
        return $this;
    }

    /**
     * Get an HTTP POST query argument
     *
     * @param string $attribute
     */
    public function getPostData($attribute, $value) {
        return isset($this->post_data[$attribute]) ? $this->post_data[$attribute] : FALSE;
    }

    /**
     * Execute query
     * @return boolean|object
     */
    public function execute() {
        $tmp = array();
        foreach ( $this->query_args as $key => $value ) {
            if ( is_bool($value) ) {
                $value = (int)$value;
            }
            $tmp[] = rawurlencode($key) . '=' . rawurlencode((string)$value);
        }
        $request_uri = $this->services_uri . '/' . $this->service . ( sizeof($tmp) > 0 ? '?' . implode('&', $tmp) : '');
        if ( empty($this->post_data) ) {
            return $this->_executeGet($request_uri);
        } else {
            // POST request
            return $this->_executePost($request_uri, $this->post_data);
        }
    }

    /**
     * Execute HTTP GET
     *
     * @param string $request_uri
     * @return boolean|object
     */
    protected function _executeGet($request_uri) {
        try {
            @$json_results = file_get_contents($request_uri);
            return json_decode($json_results);
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Execute HTTP POST
     *
     * @param string $request_uri
     * @param string $data
     * @return boolean|object
     */
    protected function _executePost($request_uri, $data) {
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
            ),
        );
        $context = stream_context_create($options);
        try {
echo file_get_contents('http://test.localhost/post_json_echo.php', FALSE, $context);
            @$json_results = file_get_contents($request_uri, FALSE, $context);
            return json_decode($json_results);
        } catch (Exception $e) {
            return FALSE;
        }
    }
}