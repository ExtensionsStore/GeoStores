<?php

/**
 * GeoStores Rules admin
 *
 * @category   ExtensionsStore
 * @package    ExtensionsStore_GeoStores
 * @author     Extensions Store <admin@extensions-store.com>
 */
class ExtensionsStore_GeoStores_Adminhtml_System_GeoStoresController extends Mage_Adminhtml_Controller_Action {
	public function updateGeoipAction() {
		$result = Mage::helper ( 'extensions_store_geostores/geoip' )->updateGeoip ();
		
		if (! $result ['error']) {
			
			Mage::getSingleton ( 'adminhtml/session' )->addSuccess ( $result ['data'] );
		} else {
			
			Mage::getSingleton ( 'adminhtml/session' )->addError ( $result ['data'] );
		}
		
		$this->_redirect ( 'adminhtml/system_config/edit', array (
				'section' => 'extensions_store_geostores' 
		) );
	}
	
	/**
	 * Create/edit rules for GeoStores
	 */
	public function rulesAction() {
		$this->loadLayout ()->renderLayout ();
	}
	
	/**
	 * Ajax select options
	 */
	public function selectAction() {
		$result = array ();
		
		$store = $this->getRequest ()->getPost ( 'store' );
		$selectType = $this->getRequest ()->getPost ( 'select_type' );
		$val = $this->getRequest ()->getPost ( 'val' );
		$op = $this->getRequest ()->getPost ( 'op' );
		
		if ($store && $selectType && $val) {
			
			$helper = Mage::helper ( 'extensions_store_geostores' );
			
			switch ($selectType) {
				
				case 'op' :
					$result ['error'] = false;
					$allowedCountriesOnly = (in_array ( $op, array (
							'!=',
							'!()' 
					) )) ? true : false;
					$result ['data'] = $helper->getOptionsHtml ( $helper->getCountryOptions ( $store, $allowedCountriesOnly ) );
					break;
				case 'country' :
					$result ['error'] = false;
					$allowedCountriesOnly = (in_array ( $op, array (
							'!=',
							'!()' 
					) )) ? true : false;
					$countryId = $val;
					$result ['data'] = $helper->getOptionsHtml ( $helper->getRegionOptions ( $store, $allowedCountriesOnly, $countryId ) );
					break;
				case 'region' :
					$result ['error'] = false;
					$result ['data'] = '';
					break;
				default :
					$result ['error'] = true;
					$result ['data'] = 'Wrong select param';
					break;
			}
		} else {
			
			$result ['error'] = true;
			$result ['data'] = 'Missing geo param';
		}
		
		return $this->getResponse ()->clearHeaders ()->setHeader ( 'Content-type', 'application/json', true )->setBody ( Mage::helper ( 'core' )->jsonEncode ( $result ) );
	}
	
	/**
	 * Save rules
	 */
	public function saveAction() {
		if ($post = $this->getRequest ()->getPost ()) {
			
			try {
				
				$stores = $post ['store'];
				
				if (is_array ( $stores ) && count ( $stores ) > 0) {
					
					$rules = Mage::getModel ( 'extensions_store_geostores/rule' )->getCollection ();
					foreach ( $rules as $rule ) {
						$rule->delete ();
					}
					
					foreach ( $stores as $i => $store ) {
						
						if (! $store) {
							continue;
						}
						
						$op = $post ['op'] [$i];
						$country = $post ['country'] [$i];
						$region = $post ['region'] [$i];
						$city = $post ['city'] [$i];
						$redirect = $post ['redirect'] [$i];
						
						$rule = Mage::getModel ( 'extensions_store_geostores/rule' );
						
						$normalizedData = array (
								'store' => $store,
								'op' => $op,
								'country' => $country,
								'region' => $region,
								'city' => $city,
								'redirect' => $redirect 
						);
						
						$rule->populate ( $normalizedData );
						
						$rule->save ();
					}
					
					$cache = Mage::app ()->getCache ();
					$cacheKey = ExtensionsStore_GeoStores_Model_GeoStores::CACHE_KEY;
					$cacheKey = md5 ($cacheKey);
					$cache->remove($cacheKey);
					
					$this->_getSession ()->addSuccess ( Mage::helper ( 'extensions_store_geostores' )->__ ( 'Rules have been saved.' ) );
				}
			} catch ( Mage_Core_Exception $e ) {
				
				$this->_getSession ()->addError ( $e->getMessage () );
			} catch ( Exception $e ) {
				
				$this->_getSession ()->addException ( $e, Mage::helper ( 'extensions_store_geostores' )->__ ( 'An error occurred while saving the rules.' ) );
			}
			
			$this->_getSession ()->setFormData ( $post );
		}
		
		$this->_redirect ( '*/*/rules' );
	}
}
