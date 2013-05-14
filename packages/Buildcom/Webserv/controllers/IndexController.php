<?php
class Buildcom_Webserv_IndexController extends Mage_Core_Controller_Front_Action
{
	const OMC_UNIQUE_ID='1573671'; // Test product from OMC
	const MAGE_ENTITY_ID = 167; // Test product (sample data in Magento)

	public function indexAction()
	{
		echo 'TEST!';
	}

	/**
	 * Show product details as listed in OMC
	 */
	public function omdirectAction() {
		$helper = Mage::helper('Buildcom_Webserv_Helper_Data');
		$helper->setService('products/' . self::OMC_UNIQUE_ID);
		//$helper->productId = '75050CB';
		//$helper->manufacturer = 'Delta';
		$product = $helper->execute();
		$this->_vardumpAsTable((array)$product);
	}

	/**
	 * Output data in a formatted table
	 * @param array $data
	 * @return boolean
	 */
	protected function _vardumpAsTable($data) {
		if ( ! is_array($data) ) {
			return FALSE;
		}

		echo '<table border="1">' . PHP_EOL;
		echo '	<tr>' . PHP_EOL;
		echo '		<th>Key</th>' . PHP_EOL;
		echo '		<th>Type</th>' . PHP_EOL;
		echo '		<th>Value</th>' . PHP_EOL;
		echo '	</tr>' . PHP_EOL;
		foreach ( $data as $key => $value ) {
			$type = gettype($value);
			echo '	<tr>' . PHP_EOL;
			echo '		<td>' . htmlentities($key) . '</td>' . PHP_EOL;
			echo '		<td>' . htmlentities($type) . '</td>' . PHP_EOL;
			echo '		<td>';
			if ( empty($type) || $type == 'array' || $type == 'object' ) {
	
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