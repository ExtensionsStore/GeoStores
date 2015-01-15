<?php

/**
 * Check user before application load
 *
 * @category   Aydus
 * @package	   Aydus_GeoStores
 * @author     Aydus Consulting <davidt@aydus.com>
 */

Mage::app();

Aydus_GeoStores_Model_GeoStores::init();

Aydus_GeoStores_Model_GeoStores::checkStore($mageRunCode, $mageRunType);

Mage::unregister('controller');