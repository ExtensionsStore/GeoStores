<?php

/**
 * GeoStores checker
 *
 * @category   ExtensionsStore
 * @package	   ExtensionsStore_GeoStores
 * @author     Extensions Store <admin@extensions-store.com>
 */
class ExtensionsStore_GeoStores_Model_GeoStores {
	const CACHE_KEY = 'ExtensionsStore_GeoStores_Model_GeoStores';
	/**
	 * Array to hold country-region-city rules
	 *
	 * @var array
	 */
	protected static $_rules;
	public static function init() {
		$cache = Mage::app ()->getCache ();
		$cacheKey = md5 ( ExtensionsStore_GeoStores_Model_GeoStores::CACHE_KEY );
		$rulesSer = $cache->load ( $cacheKey );
		self::$_rules = unserialize ( $rulesSer );
		
		if (! self::$_rules || ! is_array ( self::$_rules )) {
			
			$collection = Mage::getModel ( 'extensions_store_geostores/rule' )->getRulesCollection ();
			
			if ($collection->getSize ()) {
				
				foreach ( $collection as $rule ) {
					
					$store = $rule->getStore ();
					
					$exploded = explode ( '_', $store );
					$type = $exploded [0];
					$id = $exploded [1];
					
					if (! in_array ( $type, array (
							'website',
							'group',
							'store' 
					) ) || ! $id) {
						continue;
					}
					
					switch ($type) {
						case 'website' :
							$website = Mage::getModel ( 'core/website' )->load ( $id );
							$websiteId = $website->getId ();
							$groupId = $website->getDefaultGroupId ();
							$storeId = $website->getDefaultGroup ()->getDefaultStoreId ();
							break;
						case 'group' :
							$group = Mage::getModel ( 'core/store_group' )->load ( $id );
							$websiteId = $group->getWebsiteId ();
							$groupId = $group->getId ();
							$storeId = $group->getDefaultStoreId ();
							break;
						case 'store' :
							$store = Mage::getModel ( 'core/store' )->load ( $id );
							$websiteId = $store->getWebsiteId ();
							$groupId = $store->getGroupId ();
							$storeId = $store->getId ();
							break;
						default :
							break;
					}
					
					$region = $rule->getRegion ();
					$regions = explode ( ',', $region );
					$regionRules = array ();
					
					if (is_array ( $regions ) && count ( $regions ) > 0 && ! empty ( $regions [0] )) {
						
						foreach ( $regions as $region ) {
							
							$city = $rule->getCity ();
							$cities = explode ( ',', $city );
							$cityRules = array ();
							
							if (is_array ( $cities ) && count ( $cities ) > 0 && ! empty ( $cities [0] )) {
								
								foreach ( $cities as $city ) {
									
									$cityRules [] = array (
											'key' => 'city',
											'op' => $rule->getOp (),
											'val' => $rule->getValue (),
											'redirect' => $rule->getRedirect (),
											'rules' => array () 
									);
								}
							}
							
							$regionRules [] = array (
									'key' => 'region',
									'op' => (count ( $regionRules ) > 0) ? '==' : $rule->getOp (),
									'val' => $rule->getRegion (),
									'redirect' => $rule->getRedirect (),
									'rules' => $cityRules 
							);
						}
					}
					
					self::$_rules [$websiteId] [$groupId] [$storeId] [] = array (
							'key' => 'country',
							'op' => (count ( $regionRules ) > 0) ? '==' : $rule->getOp (),
							'val' => $rule->getCountry (),
							'redirect' => $rule->getRedirect (),
							'rules' => $regionRules 
					);
				}
				
				$rulesSer = serialize ( self::$_rules );
				
				$cache->save ( $rulesSer, $cacheKey, array (
						'CONFIG',
						'COLLECTION_DATA' 
				), 86400 );
			}
		}
		
		return self::$_rules;
	}
	
	/**
	 *
	 * @param int|Mage_Core_Model_Store $storeObjOrId        	
	 * @param geoiprecord|Mage_Customer_Model_Address $object        	
	 * @return Ambigous <boolean, array> Rule with redirect id
	 */
	public static function getRule($storeObjOrId, $object) {
		$rule = false;
		
		$storeObj = self::_getStoreObj ( $storeObjOrId );
		$storeId = $storeObj->getId ();
		
		$rules = array ();
		
		$rules = self::_getRules ( $storeObj );
		
		// if there are rules for this store
		if (is_array ( $rules ) && count ( $rules ) > 0) {
			
			$rule = self::_evalRules ( $rules, $object );
		}
		
		return $rule;
	}
	
	/**
	 *
	 * @param unknown $storeObjOrId        	
	 * @return core/store
	 */
	protected static function _getStoreObj($storeObjOrId) {
		$storeObj = Mage::getModel ( 'core/store' );
		
		if (is_object ( $storeObjOrId ) && get_class ( $storeObjOrId ) == 'Mage_Core_Model_Store') {
			
			$storeObj = $storeObjOrId;
		} else if (( int ) $storeObjOrId) {
			
			$storeId = ( int ) $storeObjOrId;
			$storeObj->load ( $storeId );
		} else {
			
			$storeId = Mage::app ()->getWebsite ()->getDefaultGroup ()->getDefaultStoreId ();
			
			$storeObj->load ( $storeId );
		}
		
		return $storeObj;
	}
	
	/**
	 *
	 * Evaluate each rule, return redirect (store group id to redirect to) if evaluated true.
	 *
	 * @param array $rules
	 *        	Redirect rules for store group
	 * @param geoiprecord|Mage_Customer_Model_Address $object        	
	 * @param string $redirect        	
	 * @return Ambigous <boolean, array> Redirect rule
	 */
	protected static function _evalRules($rules, $object, $redirect = NULL) {
		// eval rules (ors)
		foreach ( $rules as $i => $rule ) {
			
			$keyType = $rule ['key'];
			
			$key = self::_getKey ( $object, $keyType );
			
			$keyValue = ( string ) $object->$key;
			$var = self::_getVar ( $keyValue, $keyType );
			$op = $rule ['op'];
			$val = $rule ['val'];
			$lop = (isset ( $rule ['lop'] )) ? $rule ['lop'] : '||';
			
			$expr = (is_array ( $val )) ? "'$var' $op '" . implode ( "' $lop '$var' $op '", $val ) . "'" : "'$var' $op '$val'";
			$eval = "return " . $expr . ";";
			$evaluated = eval ( $eval );
			
			if ($evaluated) {
				
				// eval nested rules (ands)
				if (isset ( $rule ['rules'] ) && is_array ( $rule ['rules'] ) && count ( $rule ['rules'] )) {
					
					$rule = self::_evalRules ( $rule ['rules'], $object, $redirect );
				}
				
				// nested rules can invalidate this rule
				if ($rule) {
					// register the evaluated value for front end
					if (! Mage::registry ( 'eval_val' )) {
						Mage::register ( 'eval_val', $var );
					}
					
					return $rule;
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Return property key based on object type (geoiprecord or Mage address)
	 *
	 * @param geoiprecord|Mage_Customer_Model_Address $object        	
	 * @param string $keyType        	
	 * @return string
	 */
	protected static function _getKey($object, $keyType) {
		$key = '';
		
		if ($keyType == 'country') {
			
			if (get_class ( $object ) == 'Mage_Customer_Model_Address') {
				
				$key = 'country_id';
			} else {
				$key = 'country_code';
			}
		} else if ($keyType == 'region') {
			
			if (get_class ( $object ) == 'Mage_Customer_Model_Address') {
				
				$key = 'region_id';
			} else {
				$key = 'region';
			}
		} else if ($keyType == 'city') {
			
			if (get_class ( $object ) == 'Mage_Customer_Model_Address') {
				
				$key = 'city';
			} else {
				$key = 'city';
			}
		}
		
		return $key;
	}
	
	/**
	 * Load value from model instance when the var we're testing is numeric (i.e.
	 * address region is the region entity id)
	 *
	 * @param string|int $var        	
	 * @param string $keyType        	
	 * @return string
	 */
	protected static function _getVar($var, $keyType) {
		if (is_numeric ( $var )) {
			
			if ($keyType == 'country') {
				// @todo
			} else if ($keyType == 'region') {
				
				$regionId = ( int ) $var;
				
				$region = Mage::getModel ( 'directory/region' )->load ( $regionId );
				
				if ($region->getId ()) {
					
					$var = $region->getCode ();
				}
			} else if ($keyType == 'city') {
				// @todo
			}
		}
		
		return $var;
	}
	
	/**
	 * Return rules for this store
	 *
	 * @param Mage_Core_Model_Group $store        	
	 * @return array
	 */
	protected static function _getRules($store = null) {
		$websiteId = $store->getWebsiteId ();
		$storeGroupId = $store->getGroupId ();
		$storeId = $store->getId ();
		
		$rules = self::$_rules [$websiteId] [$storeGroupId] [$storeId];
		
		return $rules;
	}
	
	/**
	 * Check user IP record according to rules on every request
	 *
	 * @param string $mageRunCode        	
	 * @param string $mageRunType        	
	 * @return array
	 */
	public static function checkStore(&$mageRunCode = '', &$mageRunType = 'store') {
		// no need to check admin
		$request = Mage::app ()->getRequest ();
		$admin = ( string ) Mage::getConfig ()->getNode ( 'admin/routers/adminhtml/args/frontName' );
		
		$path = trim ( $request->getPathInfo (), '/' );
		
		if ($path) {
			$p = explode ( '/', $path );
		} else {
			$p = explode ( '/', ( string ) Mage::getConfig ()->getNode ( 'default/web/default/admin' ) );
		}
		
		$module = $p [0];
		
		if ($module == $admin) {
			
			return array (
					$mageRunCode,
					$mageRunType 
			);
		}
		
		$store = null;
		$storeId = null;
				
		// check store
		switch ($mageRunType){
			case 'website' :
				$website = Mage::getModel('core/website')->load($mageRunCode, 'code');
				$store = $website->getDefaultGroup ()->getDefaultStore ();
				$storeId = $store->getId();
				break;
			case 'group' :
				$group = Mage::getModel('core/store_group')->load($mageRunCode);
				$store = $group->getDefaultStore ();
				$storeId = $group->getDefaultStoreId ();
				break;
			case 'store' :
				$store = Mage::getModel('core/store')->load($mageRunCode, 'code');
				$storeId = $store->getId();
				break;
			default :
				$store = Mage::app ()->getStore ();
				$storeId = $store->getId ();
				break;
		}
				
		$geostoresEnabled = Mage::getStoreConfig ( 'extensions_store_geostores/configuration/geostores_enabled', $storeId );
		
		// api may be 0
		if (! $storeId || ! $geostoresEnabled) {
			return $mageRunCode;
		}
		
		// check if admin logged in
		$loggedin = Mage::helper ( 'extensions_store_geostores' )->adminIsLoggedin ();
		
		// if not admin
		if (! $loggedin) {
			
			// get ip record
			$session = Mage::getSingleton ( 'core/session' );
			$ip = Mage::helper ( 'extensions_store_geostores' )->getIp (true);
			$record = $session->getData ($ip);
			
			if (! $record || Mage::helper ( 'extensions_store_geostores' )->isDebug ()) {
				
				$record = Mage::helper ( 'extensions_store_geostores/geoip' )->getRecord ();
				
				$session->setData ($ip, $record );
			}
			
			// get redirect rules for this store
			$ruleAr = self::getRule ( $store, $record );
			
			// get run code
			if (is_array ( $ruleAr ) && count ( $ruleAr ) > 0) {
				
				$redirect = $ruleAr ['redirect'];
				$exploded = explode ( '_', $redirect );
				$type = $exploded [0];
				$redirectId = $exploded [1];
				
				if (in_array ( $type, array (
						'website',
						'group',
						'store' 
				) ) && ( int ) $redirectId) {
					
					$model = $type;
					
					if ($model == 'group') {
						$model = 'store_group';
					}
					$typeModel = Mage::getModel ( 'core/' . $model );
					$typeModel->load ( $redirectId );
					
					if ($type == 'store' && $typeModel->getId () && ! $typeModel->getIsActive ()) {
						$typeModel = $typeModel->getWebsite ();
						$type = 'website';
					}
					
					if ($typeModel->getId ()) {
						
						$mageRunCode = ($type == 'group') ? $typeModel->getId () : $typeModel->getCode ();
						$mageRunType = $type;
						
						if ($type == 'website') {
							
							$redirectStoreView = $typeModel->getDefaultStore ();
						} else if ($type == 'group') {
							
							$redirectStoreView = $typeModel->getDefaultStore ();
						} else {
							
							$redirectStoreView = $typeModel;
						}
						
						$storeViewCode = $redirectStoreView->getCode ();
						$storeUrl = $store->getUrl();
						$parsedStoreUrl = parse_url($storeUrl);
						$parsedStoreHost = $parsedStoreUrl['host'];
						$redirectUrl = $redirectStoreView->getUrl();
						$parsedRedirectUrl = parse_url($redirectUrl);
						$parsedRedirectHost = $parsedRedirectUrl['host'];
						if ($parsedStoreHost != $parsedRedirectHost){
							Mage::app()->getResponse()->setRedirect($redirectUrl)->sendResponse();
							exit();
						}
						
						$cookie = Mage::getSingleton ( 'core/cookie' );
						$cookie->set ( 'store', $storeViewCode, time () + 604800, '/' );
						$_COOKIE ['store'] = $storeViewCode;
						$_GET ['___store'] = $storeViewCode;
					} else {
						
						Mage::log ( "Redirect type: $type id: $redirectId does not exist or is not active.", null, 'extensions_store_geostores.log' );
					}
				}
			}
		}
		
		return array (
				$mageRunCode,
				$mageRunType 
		);
	}
}
