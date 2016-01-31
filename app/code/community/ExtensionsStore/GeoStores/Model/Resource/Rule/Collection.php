<?php

/**
 * GeoStores Rule resource collection model
 *
 * @category   ExtensionsStore
 * @package	   ExtensionsStore_GeoStores
 * @author     Extensions Store <admin@extensions-store.com>
 */
class ExtensionsStore_GeoStores_Model_Resource_Rule_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {
	protected function _construct() {
		parent::_construct ();
		$this->_init ( 'extensions_store_geostores/rule' );
	}
}
