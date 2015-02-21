<?php

/**
 * GeoStores Rule resource model
 *
 * @category   Aydus
 * @package	   Aydus_GeoStores
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_GeoStores_Model_Resource_Rule extends Mage_Core_Model_Resource_Db_Abstract
{
	
	protected function _construct()
	{
		$this->_init('aydus_geostores/rule', 'rule_id');
	}
	
}

