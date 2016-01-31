<?php

/**
 * GeoStores admin rules block
 *
 * @category   ExtensionsStore
 * @package	   ExtensionsStore_GeoStores
 * @author     Extensions Store <admin@extensions-store.com>
 */
class ExtensionsStore_GeoStores_Block_Adminhtml_System_GeoStores_Rule extends Mage_Adminhtml_Block_Widget {
	public function __construct() {
		parent::__construct ();
		$this->setTemplate ( 'extensions_store/geostores/rule.phtml' );
	}
	public function hasRule() {
		$rule = $this->getData ( 'rule' );
		
		if ($rule) {
			return true;
		}
		
		return false;
	}
	public function getRule() {
		$rule = $this->getData ( 'rule' );
		
		if ($rule) {
			return $rule;
		}
		
		return Mage::getModel ( 'extensions_store_geostores/rule' );
	}
	public function getStoreOptions() {
		$helper = Mage::helper ( 'extensions_store_geostores' );
		
		$options = $helper->getStoreOptions ();
		
		return $options;
	}
	public function getCountryOptions($store, $allowedCountriesOnly = true) {
		$helper = Mage::helper ( 'extensions_store_geostores' );
		
		$options = $helper->getCountryOptions ( $store, $allowedCountriesOnly );
		
		return $options;
	}
	public function getRegionOptions($store, $allowedCountriesOnly = true, $countryId) {
		$helper = Mage::helper ( 'extensions_store_geostores' );
		
		$options = $helper->getRegionOptions ( $store, $allowedCountriesOnly, $countryId );
		
		return $options;
	}
}

