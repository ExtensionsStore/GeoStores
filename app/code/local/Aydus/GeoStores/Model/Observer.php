<?php

/**
 * GeoStores observer
 *
 * @category   Aydus
 * @package	   Aydus_GeoStores
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_GeoStores_Model_Observer 
{
	/**
	 * Hide storeswitcher if not admin
	 */
	public function removeStoreSwitcher()
	{
		$loggedin = Mage::helper('aydus_geostores')->adminIsLoggedin();
		
		//if not admin
		if (!$loggedin && !Mage::helper('aydus_geostores')->isDebug()){
			
			$layout = Mage::app()->getLayout();
			$layout->getUpdate()->addUpdate('<remove name="store_switcher" />');
			$layout->generateXml();
		}		
	}	
	
	/**
	 * Register admin as logged in to backend
	 * 
     * @param Varien_Event_Observer $observer
	 */
	public function setAdminIsLoggedIn($observer)
	{
		$adminUser = $observer->getUser();
		$adminUserId = (int)$adminUser->getId();
						
		if ($adminUserId) {
			
			$ipAddress = Mage::helper('aydus_geostores')->getIp(true);
					
			$read = Mage::getSingleton('core/resource')->getConnection('core_read');
			$write = Mage::getSingleton('core/resource')->getConnection('core_write');
			$now = date('Y-m-d H:i:s');
			$prefix = Mage::getConfig()->getTablePrefix();
			$table = $prefix.'aydus_geostores_adminlogin';
			$tableExists = $read->fetchOne("SHOW TABLES LIKE '$table'");
			
			if ($tableExists){
				$write->query("REPLACE INTO $table (admin_user_id,ip_address,loggedin,updated_at) VALUES($adminUserId,$ipAddress,1,'$now')");
			}	
					
		} 
		
		return true;
	}
	
	/**
	 * 
	 * Install latest maxmind database
	 * 
     * @param Varien_Event_Observer $observer
	 */
	public function updateGeoip($observer)
	{
		$result = Mage::helper('aydus_geostores')->updateGeoip();
		
		if ($result['error']){
		     
		    Mage::log($result['data'], null, 'aydus_geostores.log');
		     
		} 
		
		return $result['data'];
	}    
}