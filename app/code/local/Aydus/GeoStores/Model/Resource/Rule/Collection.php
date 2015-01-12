<?php

/**
 * GeoStores Rule resource collection model
 *
 * @category   Aydus
 * @package	   Aydus_GeoStores
 * @author     Aydus Consulting <davidt@aydus.com>
 */
	
class Aydus_GeoStores_Model_Resource_Rule_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract 
{

	protected function _construct()
	{
        parent::_construct();
		$this->_init('aydus_geostores/rule');
	}
	
}
