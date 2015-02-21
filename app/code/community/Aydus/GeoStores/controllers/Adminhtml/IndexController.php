<?php

/**
 * Index admin controller
 *
 * @category   Aydus
 * @package    Aydus_GeoStores
 * @author     Aydus Consulting <davidt@aydus.com>
 */
require_once 'Mage/Adminhtml/controllers/IndexController.php';

class Aydus_GeoStores_Adminhtml_IndexController extends Mage_Adminhtml_IndexController
{
    /**
     * Disable reroute for admins
     */
    public function logoutAction()
    {
    	$adminUser = Mage::getSingleton('admin/session')->getUser();
    	
    	if ($adminUser && $adminUser->getId()) {
    		$adminUserId = (int)$adminUser->getId();
    		
    		$ipAddress = Mage::helper('aydus_geostores')->getIp(true);
    		$updatedAt = date('Y-m-d H:i:s');
    		
    		$write = Mage::getSingleton('core/resource')->getConnection('core_write');
    		$prefix = Mage::getConfig()->getTablePrefix();
    		$table = $prefix.'aydus_geostores_adminlogin';
    		
    		$write->query("REPLACE INTO $table (admin_user_id,ip_address,loggedin,updated_at) VALUES($adminUserId,$ipAddress,0,'$updatedAt')");
    	}
    	    	    	 
    	parent::logoutAction();
    }

}
