<?php
class Buildcom_Debug_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $edition = Mage::getEdition();
        $version = Mage::getVersion();
        echo '<h1><em>' . htmlentities($edition) . '</em> Edition, Version <em>' . htmlentities($version) . '</em></h1>' . PHP_EOL;
        $config = Mage::getConfig();
        var_dump($config);
    }

    public function basicAction() {
        $config = Mage::getConfig();
        echo '<h2>Areas</h2>' . PHP_EOL;
        $areas = $config->getAreas();
        foreach( $areas as $key => $value ) {
            echo '<h3>' . htmlentities($key) . '</h3>' . PHP_EOL;
            var_dump($value);
        }
        unset($areas);

        echo '<h2>Routers</h2>' . PHP_EOL;
        $routers = $config->getRouters();
        var_dump($routers);

        $params = $this->getRequest()->getParams();
        if ( ! empty($params) ) {
            var_dump($params);
        }
    }

    public function configAction() {
        $config = Mage::getConfig();
        header('Content-Type: text/xml');
        echo $config->getNode()->asXML();
    }

    public function systemAction() {
        $config = Mage::getConfig();
        //header('Content-Type: text/xml');
        echo $config->getNode('modules/Mage_Checkout')->asXML();

        var_dump($config->getAreaConfig('adminhtml'));
        exit;
    }

    public function designAction() {
        $design = Mage::getDesign();
        echo 'Theme: ' . $design->getDesignTheme()->getFullPath() . '<br />' . PHP_EOL;
    }
}