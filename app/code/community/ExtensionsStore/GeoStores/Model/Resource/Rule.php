<?php

/**
 * GeoStores Rule resource model
 *
 * @category   ExtensionsStore
 * @package	   ExtensionsStore_GeoStores
 * @author     Extensions Store <admin@extensions-store.com>
 */
class ExtensionsStore_GeoStores_Model_Resource_Rule extends Mage_Core_Model_Resource_Db_Abstract {
	protected function _construct() {
		$this->_init ( 'extensions_store_geostores/rule', 'rule_id' );
	}
}

