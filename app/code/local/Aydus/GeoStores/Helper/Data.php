<?php

/**
 * GeoStores helper
 *
 * @category   Aydus
 * @package	   Aydus_GeoStores
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_GeoStores_Helper_Data extends Mage_Core_Helper_Abstract
{
	protected $_geoip;
	
	/**
	 * Load geoip database file
	 */
	public function __construct()
	{
		try {
			
			$dir = __DIR__;
			chdir($dir.DS.'..');
			//include geoip scripts
			include('lib'.DS.'geoipcity.inc');
			
			//include geoip database
			$geoipDb = Mage::getBaseDir().Mage::getStoreConfig('aydus_geostores/configuration/geoip_db');
			
			if ($geoipDb && file_exists($geoipDb)){
			    	
			    $geoipDb = str_replace('/',DS,$geoipDb);
			    $path = $geoipDb;
			
			} else if (file_exists(Mage::getBaseDir('var').DS.'aydus'.DS.'geostores'.DS.'GeoIPCity.dat')){
				
				$path = Mage::getBaseDir('var').DS.'aydus'.DS.'geostores'.DS.'GeoIPCity.dat';
				
			} else {
			    	
			    $path = 'lib'.DS.'GeoLiteCity.dat';
			}
			
			//open database file
			$this->_geoip = geoip_open($path, GEOIP_STANDARD);
			chdir(Mage::getBaseDir());
						
		} catch(Exception $e){
			
			Mage::log($e->getMessage(),null,'aydus_geostores.log');
		}

	}
	
	/**
	 * Get user's geoip record
	 * 
	 * @param string $ip
	 * @return geoiprecord|boolean
	 */
	public function getRecord($ip)
	{
		try {
			
			if (!$ip){
				$ip = $this->getIp();
			}
							
			$record = geoip_record_by_addr($this->_geoip, $ip);
			$record = $this->fixObject($record);
			return $record;
				
		}catch (Exception $e){
				
			Mage::log($e->getMessage(),null,'aydus_geostores.log');
		}
	
		return false;
	}
		
	/**
	 *
	 * @param string $long
	 * @return Ambigous <number, string>
	 */
	public function getIp($long = false)
	{
		$request = Mage::app()->getRequest();
	
		if ($request->getServer('HTTP_CLIENT_IP')){
			$ip = $request->getServer('HTTP_CLIENT_IP');
		} else if ($request->getServer('HTTP_X_FORWARDED_FOR')){
			$ip = $request->getServer('HTTP_X_FORWARDED_FOR');
		} else {
			$ip = $request->getServer('REMOTE_ADDR');
		}
	
		if ($long){
				
			$ip = ip2long($ip);
		}
	
		return $ip;
	}

	/**
	 *
	 * @param unknown $object
	 * @see http://stackoverflow.com/questions/965611/forcing-access-to-php-incomplete-class-object-properties
	 * @return mixed
	 */
	public function fixObject(&$object)
	{
		if (!is_object ($object) && gettype ($object) == 'object'){
				
			return ($object = unserialize (serialize ($object)));
		}
	
		return $object;
	}
	
	/**
	 *	See if admin is logged in
	 *
	 *	@return boolean
	 */
	public function adminIsLoggedin()
	{
		$loggedin = false;
		
		$read = Mage::getSingleton('core/resource')->getConnection('core_read');
		$ipAddress = $this->getIp(true);
		$now = date('Y-m-d H:i:s');
		$oneHourAgo = date('Y-m-d H:i:s', time() - 3601);
		
		$prefix = Mage::getConfig()->getTablePrefix();
		$table = $prefix.'aydus_geostores_adminlogin';

		$key = md5($table);
		$cache = Mage::app()->getCache();
		$tableExists = $cache->load($key);
		
		if (!$tableExists || $tableExists != $table){
			$tableExists = $read->fetchOne("SHOW TABLES LIKE '$table'");
			$cache->save($tableExists, $key, array(), 604800);
		}		
		
		if ($tableExists && $tableExists == $table){
			$sql = "SELECT loggedin FROM $table WHERE ip_address = '$ipAddress' AND updated_at > '$oneHourAgo'";
			$loggedin = (int)$read->fetchOne($sql);
		}
		
		return $loggedin;
	}	
	
	/**
	 * Debug
	 * @return boolean
	 */
	public function isDebug()
	{
		$debug = Mage::getStoreConfig('aydus_geostores/configuration/debug');

		return $debug;
	}	
	
	/**
	 *
	 * Install latest maxmind database
	 *
	 */
	public function updateGeoip()
	{
		$result = array();
		
		try {
				
			$url = Mage::getStoreConfig('aydus_geostores/configuration/geoip_download_url');
				
			if ($url){
	
				$targetPath = sys_get_temp_dir();
				$filename = $targetPath . 'tmpfile';
				$headerBuff = fopen ( $targetPath.'/headers', 'w+' );
				$fileTarget = fopen ( $filename, 'w' );
					
				$ch = curl_init ( $url );
				curl_setopt ( $ch, CURLOPT_WRITEHEADER, $headerBuff );
				curl_setopt ( $ch, CURLOPT_FILE, $fileTarget );
				curl_exec ( $ch );
					
				if (! curl_errno ( $ch )) {
					rewind ( $headerBuff );
					$headers = stream_get_contents ( $headerBuff );
					if (preg_match ( '/Content-Disposition: .*filename=([^ ]+)/', $headers, $matches )) {
							
						$archive = $matches [1];
						$tarPath = $targetPath . $archive;
						rename ( $filename, $tarPath );
						$phar = new PharData($tarPath);
	
						$varPath = Mage::getBaseDir('var').DS.'aydus'.DS.'geostores';
						$phar->extractTo($varPath, null, true);
	
						$dir = substr($archive, 0, strpos($archive, '.tar.gz'));
						$source = $varPath.DS.$dir.DS.'GeoIPCity.dat';
						$dest = $varPath.'/GeoIPCity.dat';
						rename($source, $dest);
						
						if (file_exists($dest)){
							
							$result['error'] = false;
							$result['data'] = Mage::helper('aydus_geostores')->__('GeoIP Database has been updated');
								
						} else {
							
							$result['error'] = true;
							$result['data'] = Mage::helper('aydus_geostores')->__('An error occurred while moving the database');
						}
						
					} else {
						
						$result['error'] = true;
						$result['data'] = Mage::helper('aydus_geostores')->__('An error occurred during download');
						
					}
				} else {
					
					$result['error'] = true;
					$result['data'] = Mage::helper('aydus_geostores')->__('An error occurred executing the download');
				}
					
				curl_close ( $ch );
				
			} else {
				
				$result['error'] = true;
				$result['data'] = Mage::helper('aydus_geostores')->__('Please set the download url');
			}
	
		} catch (Exception $e){
				
			Mage::log($e->getMessage(), null, 'aydus_geostores.log');
			$result['error'] = true;
			$result['data'] = $e->getMessage();
		}
	
		return $result;
	}	
	
    public function getStoreOptions()
    {
    	$options = array();
    	$websites = Mage::getModel('core/website')->getCollection();
    	
    	if ($websites->getSize()>0){
    	
    	    foreach ($websites as $website){
    	
    	        $options[] = array('value'=>'website_'.$website->getId(), 'label'=> $website->getName(), 'class'=>'website');
    	
    	        $groups = $website->getGroupCollection();
    	
    	        if ($groups->getSize() > 0){
    	
    	            foreach ($groups as $group){
    	
    	                $options[] = array('value'=>'group_'.$group->getId(), 'label'=> '&nbsp;&nbsp;'.$group->getName(), 'class'=>'group');
    	
    	                $stores = $group->getStoreCollection();
    	
    	                if ($stores->getSize()){
    	
    	                    foreach ($stores as $store){
    	
    	                        $options[] = array('value'=>'store_'.$store->getId(), 'label'=> '&nbsp;&nbsp;&nbsp;&nbsp;'.$store->getName(), 'class'=>'store');
    	
    	                    }
    	                }
    	
    	            }
    	        }
    	
    	    }
    	
    	}

    	return $options;
    }	
	
	public function getScope($store)
	{
	    $exploded = explode('_',$store);
	    $scope = $exploded[0];
	    $scopeId = $exploded[1];
	    	
	    switch ($scope){
	        case 'website':
	            $scope = 'websites';
	            break;
	        case 'group':
	            $scope = 'stores';
	            $scopeId = Mage::getModel('core/store_group')->load($scopeId)->getDefaultStore()->getId();
	            break;
	        case 'store':
	            $scope = 'stores';
	            break;
	    }
	    
	    return array($scope, $scopeId);
	}	
	
	protected function _getAllowedCountries($scope, $scopeId)
	{
		if ($scope == 'websites'){
			$website = Mage::getModel('core/website')->load($scopeId);
			$allowedCountriesConfig = $website->getConfig('general/country/allow');
		} else {
			$allowedCountriesConfig = Mage::getStoreConfig('general/country/allow', $scopeId);
		}
		
		if (!$allowedCountriesConfig){
			$allowedCountriesConfig = Mage::getStoreConfig('general/country/allow', 0);
		}
		
		$allowedCountries = explode(',',$allowedCountriesConfig);

		return $allowedCountries;
	}
	
	public function getOptionsHtml($options, $value = null)
	{
		$optionsHtml = '';
		if (count($options)){
			foreach ($options as $option){
				$selected = ($value && $value == $option['value']) ? 'selected="selected"' : '';
				$optionsHtml.= "<option value=\"{$option['value']}\" $selected>{$option['label']}</option>";
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
	public function getCountryOptions($store, $allowedCountriesOnly=true)
	{
		$options = array();
		$countriesCollection = Mage::getResourceModel('directory/country_collection');
		
		if ($allowedCountriesOnly){
			list($scope, $scopeId) = $this->getScope($store);
			$allowedCountries = $this->_getAllowedCountries($scope, $scopeId);
			$countriesCollection->addFieldToFilter('country_id', array('in'=>$allowedCountries));
		}
		
		$options = $countriesCollection->toOptionArray();

		return $options;
	}
	
	/**
	 * Get Regions for specific Countries
	 * @param string $storeId
	 * @return array|null
	 */
	public function getRegionOptions($store, $allowedCountriesOnly=true, $countryId)
	{
		$options = array();
		$regionsCollection = Mage::getResourceModel('directory/region_collection');
		if ($allowedCountriesOnly){
    		list($scope, $scopeId) = $this->getScope($store);
    		$allowedCountries = $this->_getAllowedCountries($scope, $scopeId);
    		
    		$regionsCollection->addFieldToFilter('country_id', array('in'=>$allowedCountries));
		}
		
		$regionsCollection->addFieldToFilter('country_id', $countryId);		

		$options = $regionsCollection->toOptionArray();
		
		return $options;
	}	
	
}