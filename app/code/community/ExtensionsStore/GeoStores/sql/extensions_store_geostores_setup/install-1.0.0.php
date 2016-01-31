<?php
$this->startSetup ();
echo 'Starting GeoStores Setup...<br />';

$varPath = Mage::getBaseDir ( 'var' ) . DS . 'extensions_store' . DS . 'geostores';
mkdir ( $varPath, 0777, true );

$this->run ( "CREATE TABLE IF NOT EXISTS {$this->getTable('extensions_store_geostores_adminlogin')} (
`admin_user_id` INT(11) UNSIGNED NOT NULL,
`ip_address` BIGINT(20) UNSIGNED NOT NULL,
`loggedin` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
`updated_at` DATETIME NOT NULL,
PRIMARY KEY ( `admin_user_id` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;" );

$this->run ( "CREATE TABLE IF NOT EXISTS {$this->getTable('extensions_store_geostores_rule')} (
`rule_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`store` VARCHAR(50) NOT NULL,
`parent_id` INT(11) UNSIGNED NOT NULL,
`key` VARCHAR(20) NOT NULL,
`op` VARCHAR(20) NOT NULL,
`value` VARCHAR(512) NOT NULL,
`redirect` VARCHAR(50) NOT NULL,
PRIMARY KEY ( `rule_id` )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;" );

echo 'Ended GeoStores Setup';
$this->endSetup ();