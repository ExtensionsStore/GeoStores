<?php

/**
 * GeoStores admin rules block
 *
 * @category   Aydus
 * @package	   Aydus_GeoStores
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_Geostores_Block_Adminhtml_System_GeoStores_Rules extends Mage_Adminhtml_Block_Widget
{
	
	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('aydus/geostores/rules.phtml');
	}
		
	public function hasRules()
	{
	    return $this->getRulesCollection()->getSize();
	}
	
	public function getRulesCollection()
	{
		$rulesCollection = Mage::getSingleton('aydus_geostores/rule')->getRulesCollection();
		
	    return $rulesCollection;
	}
	
}

