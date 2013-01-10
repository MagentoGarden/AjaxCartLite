<?php
/**
 * MagentoGarden
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE_AFL.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentogarden.com so we can send you a copy immediately.
 *
 * @category    helper
 * @package     magentogarden_ajaxcartlite
 * @copyright   Copyright (c) 2012 MagentoGarden Inc. (http://www.magentogarden.com)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version		1.1
 * @author		Alan Marcus (alan.marcus@magentogarden.com);
 */

class MagentoGarden_AjaxCart_Helper_Data extends Mage_Core_Helper_Abstract
{
	/**
	 * getProductIdHtml
	 * @param Mage_Catalog_Model_Product Magento Product Information
	 * @return string an html input tag indicate the product id
	 */
	public function getProductIdHtml($product) {
		$_product_id = $product->getId();
		return "<input type='hidden' value='$_product_id' class='mg-ajaxcart-pd'/>";
	}
	
	/**
	 * get_form_key
	 * @return string form key
	 */
    public function get_form_key() {
    	$_form_key = Mage::getSingleton('core/session')->getFormKey();
    	return $_form_key;
    }
    
	/**
	 * get_add_url
	 * @return string "add to cart" url 
	 */
    public function get_add_url() {
    	return $this->_getUrl('ajaxcart/cart/add');
    }
    
	/**
	 * get_info_url()
	 * @return string url to get info after successfully add to cart
	 */
    public function get_info_url() {
    	return $this->_getUrl('ajaxcart/cart/getinfo');
    }
    
	/**
	 * get_cart_url()
	 * @return string shopping cart url
	 */
    public function get_cart_url() {
    	return $this->_getUrl('checkout/cart/index');
    }
    
	/**
	 * is_enabled()
	 * To check whether the AjaxCart module is enabled
	 * @return bool 
	 */
	public function is_enabled() {
		return (Mage::getStoreConfig('ajaxcart/general/enabled') == 1);
	}
	
	public function get_thumbnail_width() {
		return Mage::getStoreConfig('ajaxcart/popup/thumbnail_width');	
	}

	public function get_thumbnail_height() {
		return Mage::getStoreConfig('ajaxcart/popup/thumbnail_height');
	}
}