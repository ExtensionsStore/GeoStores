<?php

/**
 * Check user before application load
 *
 * @category   ExtensionsStore
 * @package	   ExtensionsStore_GeoStores
 * @author     Extensions Store <admin@extensions-store.com>
 */
Mage::app ();

ExtensionsStore_GeoStores_Model_GeoStores::init ();

ExtensionsStore_GeoStores_Model_GeoStores::checkStore ( $mageRunCode, $mageRunType );

Mage::unregister ( 'controller' );