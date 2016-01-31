<?php

/**
 * System configuration form button
 *
 * @category   ExtensionsStore
 * @package	   ExtensionsStore_GeoStores
 * @author     Extensions Store <admin@extensions-store.com>
 */
class ExtensionsStore_GeoStores_Block_Adminhtml_System_Config_Form_Button extends Mage_Adminhtml_Block_System_Config_Form_Field {
	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
		$this->setElement ( $element );
		$url = $this->getUrl ( 'adminhtml/system_geostores/updategeoip' );
		
		$html = $this->getLayout ()->createBlock ( 'adminhtml/widget_button' )->setType ( 'button' )->setClass ( 'scalable' )->setLabel ( 'Update Now' )->setOnClick ( "setLocation('$url')" )->toHtml ();
		
		return $html;
	}
}
