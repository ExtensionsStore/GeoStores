<?php

/**
 * GeoStores helper
 *
 * @category   ExtensionsStore
 * @package	   ExtensionsStore_GeoStores
 * @author     Extensions Store <admin@extensions-store.com>
 */
class ExtensionsStore_GeoStores_Helper_Data extends Mage_Core_Helper_Abstract {
	/**
	 *
	 * @param string $long        	
	 * @return Ambigous <number, string>
	 */
	public function getIp($long = false) {
		return Mage::helper ( 'extensions_store_geostores/geoip' )->getIp ( $long );
	}
	
	/**
	 * See if admin is logged in
	 *
	 * @return boolean
	 */
	public function adminIsLoggedin() {
		$loggedin = false;
		
		$read = Mage::getSingleton ( 'core/resource' )->getConnection ( 'core_read' );
		$ipAddress = $this->getIp ( true );
		$now = date ( 'Y-m-d H:i:s' );
		$oneHourAgo = date ( 'Y-m-d H:i:s', time () - 3601 );
		
		$prefix = Mage::getConfig ()->getTablePrefix ();
		$table = $prefix . 'extensions_store_geostores_adminlogin';
		
		$key = md5 ( $table );
		$cache = Mage::app ()->getCache ();
		$tableExists = $cache->load ( $key );
		
		if (! $tableExists || $tableExists != $table) {
			$tableExists = $read->fetchOne ( "SHOW TABLES LIKE '$table'" );
			$cache->save ( $tableExists, $key, array (), 604800 );
		}
		
		if ($tableExists && $tableExists == $table) {
			$sql = "SELECT loggedin FROM $table WHERE ip_address = '$ipAddress' AND updated_at > '$oneHourAgo'";
			$loggedin = ( int ) $read->fetchOne ( $sql );
		}
		
		return $loggedin;
	}
	
	/**
	 * Debug
	 * 
	 * @return boolean
	 */
	public function isDebug() {
		$debug = Mage::getStoreConfig ( 'extensions_store_geostores/configuration/debug' );
		
		return $debug;
	}
	public function getStoreOptions() {
		$options = array ();
		$websites = Mage::getModel ( 'core/website' )->getCollection ();
		
		if ($websites->getSize () > 0) {
			
			foreach ( $websites as $website ) {
				
				$options [] = array (
						'value' => 'website_' . $website->getId (),
						'label' => $website->getName (),
						'class' => 'website' 
				);
				
				$groups = $website->getGroupCollection ();
				
				if ($groups->getSize () > 0) {
					
					foreach ( $groups as $group ) {
						
						$options [] = array (
								'value' => 'group_' . $group->getId (),
								'label' => '&nbsp;&nbsp;' . $group->getName (),
								'class' => 'group' 
						);
						
						$stores = $group->getStoreCollection ();
						
						if ($stores->getSize ()) {
							
							foreach ( $stores as $store ) {
								
								$options [] = array (
										'value' => 'store_' . $store->getId (),
										'label' => '&nbsp;&nbsp;&nbsp;&nbsp;' . $store->getName (),
										'class' => 'store' 
								);
							}
						}
					}
				}
			}
		}
		
		return $options;
	}
	public function getScope($store) {
		$exploded = explode ( '_', $store );
		$scope = $exploded [0];
		$scopeId = $exploded [1];
		
		switch ($scope) {
			case 'website' :
				$scope = 'websites';
				break;
			case 'group' :
				$scope = 'stores';
				$scopeId = Mage::getModel ( 'core/store_group' )->load ( $scopeId )->getDefaultStore ()->getId ();
				break;
			case 'store' :
				$scope = 'stores';
				break;
		}
		
		return array (
				$scope,
				$scopeId 
		);
	}
	protected function _getAllowedCountries($scope, $scopeId) {
		if ($scope == 'websites') {
			$website = Mage::getModel ( 'core/website' )->load ( $scopeId );
			$allowedCountriesConfig = $website->getConfig ( 'general/country/allow' );
		} else {
			$allowedCountriesConfig = Mage::getStoreConfig ( 'general/country/allow', $scopeId );
		}
		
		if (! $allowedCountriesConfig) {
			$allowedCountriesConfig = Mage::getStoreConfig ( 'general/country/allow', 0 );
		}
		
		$allowedCountries = explode ( ',', $allowedCountriesConfig );
		
		return $allowedCountries;
	}
	public function getOptionsHtml($options, $value = null) {
		$optionsHtml = '';
		if (count ( $options )) {
			foreach ( $options as $option ) {
				$selected = ($value && $value == $option ['value']) ? 'selected="selected"' : '';
				$optionsHtml .= "<option value=\"{$option['value']}\" $selected>{$option['label']}</option>";
			}
		}
		
		return $optionsHtml;
	}
	
	/**
	 *
	 * @param string $store        	
	 * @param bool $allowedCountriesOnly        	
	 * @return array
	 */
	public function getCountryOptions($store, $allowedCountriesOnly = true) {
		$options = array ();
		$countriesCollection = Mage::getResourceModel ( 'directory/country_collection' );
		
		if ($allowedCountriesOnly) {
			list ( $scope, $scopeId ) = $this->getScope ( $store );
			$allowedCountries = $this->_getAllowedCountries ( $scope, $scopeId );
			$countriesCollection->addFieldToFilter ( 'country_id', array (
					'in' => $allowedCountries 
			) );
		}
		
		$options = $countriesCollection->toOptionArray ();
		
		return $options;
	}
	
	/**
	 * Get Regions for specific Countries
	 * 
	 * @param string $storeId        	
	 * @return array|null
	 */
	public function getRegionOptions($store, $allowedCountriesOnly = true, $countryId) {
		$options = array ();
		$regionsCollection = Mage::getResourceModel ( 'directory/region_collection' );
		if ($allowedCountriesOnly) {
			list ( $scope, $scopeId ) = $this->getScope ( $store );
			$allowedCountries = $this->_getAllowedCountries ( $scope, $scopeId );
			
			$regionsCollection->addFieldToFilter ( 'country_id', array (
					'in' => $allowedCountries 
			) );
		}
		
		$regionsCollection->addFieldToFilter ( 'country_id', $countryId );
		
		$options = $regionsCollection->toOptionArray ();
		
		return $options;
	}
}