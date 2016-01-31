<?php

/**
 * GeoStores admin rules block
 *
 * @category   ExtensionsStore
 * @package	   ExtensionsStore_GeoStores
 * @author     Extensions Store <admin@extensions-store.com>
 */
class ExtensionsStore_GeoStores_Block_Adminhtml_System_GeoStores_Rules extends Mage_Adminhtml_Block_Widget {
	public function __construct() {
		parent::__construct ();
		$this->setTemplate ( 'extensions_store/geostores/rules.phtml' );
	}
	public function hasRules() {
		return $this->getRulesCollection ()->getSize ();
	}
	public function getRulesCollection() {
		$rulesCollection = Mage::getSingleton ( 'extensions_store_geostores/rule' )->getRulesCollection ();
		
		return $rulesCollection;
	}
}

