<?php

/**
 * GeoIP helper
 *
 * @category   Aydus
 * @package	   Aydus_GeoStores
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_GeoStores_Helper_Geoip extends Mage_Core_Helper_Abstract
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
			
			$libPath = Mage::getBaseDir().DS.'lib'.DS.'Aydus'.DS.'geoipcity.inc';
				
			if (file_exists($libPath)){
			    include_once($libPath);
			} else {
			    //include geoip scripts
			    include_once('lib'.DS.'geoipcity.inc');
			}
			
			//include geoip database
			$geoipDb = Mage::getStoreConfig('aydus_geostores/configuration/geoip_db');
			
			if ($geoipDb && file_exists(Mage::getBaseDir().$geoipDb)){
			    	
			    $geoipDb = str_replace('/',DS,Mage::getBaseDir().$geoipDb);
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
	public function getRecord($ip = null)
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
				$filename = $targetPath .DS. 'tmpfile';
				$headerBuff = fopen ( $targetPath.DS.'headers', 'w+' );
				$fileTarget = fopen ( $filename, 'w' );
					
				$ch = curl_init ( $url );
				curl_setopt($ch, CURLOPT_WRITEHEADER, $headerBuff );
				curl_setopt($ch, CURLOPT_FILE, $fileTarget );
				curl_exec( $ch );
					
				if (! curl_errno ( $ch )) {
					rewind ( $headerBuff );
					$headers = stream_get_contents ( $headerBuff );
					if (preg_match ( '/Content-Disposition: .*filename=([^\s]+)/', $headers, $matches )) {
													
						$archive = $matches [1];
						$tarPath = $targetPath .DS. $archive;
						rename ( $filename, $tarPath );
						$phar = new PharData($tarPath);
	
						$varPath = Mage::getBaseDir('var').DS.'aydus';
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
				
			Mage::log($e->getMessage(), null, 'aydus_storelegal.log');
			$result['error'] = true;
			$result['data'] = $e->getMessage();
		}
	
		return $result;
	}	
	
}