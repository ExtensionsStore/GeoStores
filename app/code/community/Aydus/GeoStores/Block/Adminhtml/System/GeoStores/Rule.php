<?php

/**
 * GeoStores admin rules block
 *
 * @category   Aydus
 * @package	   Aydus_GeoStores
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_GeoStores_Block_Adminhtml_System_GeoStores_Rule extends Mage_Adminhtml_Block_Widget
{
	
	public function __construct()
	{
		parent::__construct();
		$this->setTemplate('aydus/geostores/rule.phtml');
	}	
	
    public function hasRule()
    {
    	$rule = $this->getData('rule');
    	
    	if ($rule){
    		return true;
    	}
    	
    	return false;
    }
    
    public function getRule()
    {
    	$rule = $this->getData('rule');
    	 
    	if ($rule){
    	    return $rule;
    	}   

    	return Mage::getModel('aydus_geostores/rule');
    }
	
	public function getStoreOptions()
	{
		$helper = Mage::helper('aydus_geostores');
		
		$options = $helper->getStoreOptions();
		
	    return $options;
	}
		
	public function getCountryOptions($store, $allowedCountriesOnly = true)
	{
		$helper = Mage::helper('aydus_geostores');
				
		$options = $helper->getCountryOptions($store, $allowedCountriesOnly);
		
	    return $options;
	}
		
	public function getRegionOptions($store, $allowedCountriesOnly = true, $countryId)
	{
	    $helper = Mage::helper('aydus_geostores');
	
	    $options = $helper->getRegionOptions($store, $allowedCountriesOnly, $countryId);
	
	    return $options;
	}	
	
}

