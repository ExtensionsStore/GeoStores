<?php

/**
 * GeoStores Rule
 *
 * @category   Aydus
 * @package	   Aydus_GeoStores
 * @author     Aydus Consulting <davidt@aydus.com>
 */

class Aydus_GeoStores_Model_Rule extends Mage_Core_Model_Abstract
{
	/**
	 *  Collection for singleton
	 *  
	 * @var Aydus_GeoStores_Model_Resource_Rule_Collection
	 */
	protected $_collection;
	
	/**
	 * Valid operators
	 * 
	 * @var array
	 */
	public static $_ops = array('==' => 'is', '!=' => 'is not','()' => 'is one of','!()' => 'is not one of');
		
	/**
	 * Initialize resource model
	 */
	protected function _construct()
	{
		parent::_construct();
	
		$this->_init('aydus_geostores/rule');
	}
	
	/**
	 * 
	 * @return array
	 */
	public function getOps()
	{
	    return self::$_ops;
	}
	
	/**
	 * 
	 * @return array
	 */
	public function getOpsOptionArray()
	{
	    $options = array();
	
	    foreach (self::$_ops as $value=>$label){
	        $options[] = array('value'=>$value, 'label'=> $label);
	    }
	
	    return $options;
	}
	
	public function getSelectedStoreLabel($key='store')
	{
		$storeValue = $this->getData($key);
		if ($storeValue){
		
		    $helper = Mage::helper('aydus_geostores');
		    $storeOptions = $helper->getStoreOptions();
		
		    foreach ($storeOptions as $option){
		        if ($storeValue == $option['value']){
		            return str_replace('&nbsp;','',$option['label']);
		        }
		    }
		}		
		
	    return '...';
	}

	/**
	 * Get the selected op label
	 * 
	 * @return string|null
	 */
	public function getSelectedOpLabel()
	{
		$opValue = $this->getData('op');
		if ($opValue){
			foreach (self::$_ops as $value=>$label){
			    if ($opValue == $value){
			        return $label;
			    }
			}
		}
		
		return '...';
	}
	
	/**
	 * Get the selected country label
	 *
	 * @return string|null
	 */
	public function getSelectedCountryLabel()
	{
	    $countryValue = $this->getData('country');
	    if ($countryValue){
	    	
	    	$helper = Mage::helper('aydus_geostores');
	    	$store = $this->getData('store');
	    	$countryOptions = $helper->getCountryOptions($store);
	    	
	    	foreach ($countryOptions as $option){
	    	    if ($countryValue == $option['value']){
	    	        return $option['label'];
	    	    }
	    	}
	    }
	    
	    return '...';
	}	
	
	/**
	 * Get the selected region label
	 *
	 * @return string|null
	 */
	public function getSelectedRegionLabel()
	{
	    $regionValue = $this->getRegionId();

	    if ($regionValue){
	
	        $helper = Mage::helper('aydus_geostores');
	        $store = $this->getData('store');
	        $countryId = $this->getData('country');
	        $regionOptions = $helper->getRegionOptions($store, true, $countryId);
	
	        if ($regionOptions){
	        	foreach ($regionOptions as $option){
	        	    if ($regionValue == $option['value']){
	        	        return $option['label'];
	        	    }
	        	}
	        	
	        }
	    }
	    
	    return '...';
	}	
	
	/**
	 * Get the selected region label
	 *
	 * @return string|null
	 */
	public function getSelectedCityLabel()
	{
	    $cityValue = $this->getData('city');
	    if ($cityValue){
	       return $cityValue;
	    }
	     
	    return '...';
	}	
	
	/**
	 * Get singleton collection of normalized rules
	 */
	public function getRulesCollection()
	{
		if (!$this->_collection){
			
			$subquery = new Zend_Db_Expr('IF (`region_table`.`op`, `region_table`.`op`, `main_table`.`op`) AS op');
			
			$collection = $this->getCollection();
			$collection->getSelect()->reset(Zend_Db_Select::COLUMNS)->columns(array('store', 'value AS country', 'redirect', $subquery));
			$collection->getSelect()->joinLeft( array('region_table'=> $collection->getMainTable()), 'main_table.rule_id = region_table.parent_id', array('region_table.value AS region'));
			$collection->getSelect()->joinLeft( array('city_table'=> $collection->getMainTable()), 'region_table.rule_id = city_table.parent_id', array('city_table.value AS city'));
			$collection->getSelect()->where('main_table.parent_id = 0');
			 
			$selectStr = (string)$collection->getSelect();			
						
			$this->_collection = $collection;
		}
		        
		return $this->_collection;
	}
	
	/**
	 * Get selected region id
	 * 
	 * @param string|null $regionCode
	 * @return null|int
	 */
	public function getRegionId($regionCode = null)
	{
		if (!$regionCode){
			$regionCode = $this->getRegion();
		}
		
		if ($regionCode){
			
			$regions = explode(',',$regionCode);
			if (is_array($regions) && count($regions) > 1){
			    	
			    $regionCode = $regions[0];
			}	

			$countryId = $this->getCountry();
			
			$regionModel = Mage::getModel('directory/region')->loadByCode($regionCode, $countryId);
			
			if ($regionModel->getId()){
			    return $regionModel->getId();
			}			
		
		}
		
		return null;
	}
	
	/**
	 * Set data from normal data
	 * 
	 * @param array $normalizedData
	 */
	public function populate($normalizedData)
	{				
		$store = $normalizedData['store'];
		$op = $normalizedData['op'];
		$country = $normalizedData['country'];
		$region = $normalizedData['region'];
		$city = $normalizedData['city'];
		$redirect = $$normalizedData['redirect'];
		$parentId = 0;
		
		if ($city){
			
			$countryRule = Mage::getModel('aydus_geostores/rule');
			$countryData = array(
			        'store' => $store,
			        'key' => 'country',
			        'op' => '==',
			        'value' => $country,
			        'redirect' => $redirect,
			);
			$countryRule->setData($countryData);
			$countryRule->save();
			$countryId = $countryRule->getId();
				
			$regionRule = Mage::getModel('aydus_geostores/rule');
			$regionData = array(
			        'store' => $store,
					'parent_id' => $countryId,
			        'key' => 'region',
			        'op' => '==',
			        'value' => $region,
			        'redirect' => $redirect,
			);
			$regionRule->setData($regionData);
			$regionRule->save();
			$parentId = $regionRule->getId();
						
		    $key = 'city';
		    $value = $city;
		    
		} else if ($region){
		
		    $countryRule = Mage::getModel('aydus_geostores/rule');
		    $countryData = array(
		            'store' => $store,
		            'key' => 'country',
		            'op' => '==',
		            'value' => $country,
		            'redirect' => $redirect,
		    );
		    $countryRule->setData($countryData);
		    $countryRule->save();
		    $parentId = $countryRule->getId();
		
		    $key = 'region';
		    $value = $region;
		    
		    if (is_numeric($value)){
		        $region = Mage::getModel('directory/region')->load($value);
		        $value = $region->getCode();
		    }
		    
		} else {
			
		    $key = 'country';
		    $value = $country;
		}
		
		$data = array(
			'store' => $store,
			'parent_id' => $parentId,
			'key' => $key,
			'op' => $op,
			'value' => $value,
			'redirect' => $redirect,
		);
		
		$this->setData($data);
	}
	
}