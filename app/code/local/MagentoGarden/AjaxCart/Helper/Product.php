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
 * @version     1.1
 * @author      Alan Marcus (alan.marcus@magentogarden.com);
 */

class MagentoGarden_AjaxCart_Helper_Product extends Mage_Core_Helper_Abstract {
		
	private function _get_configurable_options($_product) {
		$_result = array();
		$_options  = $_product->getTypeInstance(true)->getConfigurableAttributesAsArray($_product);
		foreach ($_options as $_option) {
			$_result[] = $_option->getData();
		}
		return $_result;
	}
	
	private function _get_custom_options($_product) {
		$_result = array();
		$_options = $_product->getOptions();
		foreach ($_options as $_option) {
			$_option_data = $_option->getData();
			$_value_collection = array();
			if ($_option_data['type'] == 'drop_down' || $_option_data['type'] == 'checkbox') {
				$_values = $_option->getValues();
				foreach ($_values as $_value) {
					$_data = $_value->getData();
					$_formatted_price = Mage::helper('core')->currency($_data['price']);
					$_formatted_price = strip_tags($_formatted_price);
					$_data['formatted'] = $_formatted_price;
					$_value_collection[] = $_data;

				}
			}
			
			$_result[] = array(
				'title' => $_option_data['title'],
				'type' => $_option_data['type'],
				'name' => 'options['.$_option_data['option_id'].']',
				'values' => $_value_collection,
			);
		}
		return $_result;
	}	
		
	private function _has_custom_options($_product) {
		return (count($_product->getOptions()) > 0);
	}
	
	private function _is_configurable_product($_product) {
		return ($_product->getTypeId() == 'configurable');
	}
		
	private function _reload($_product) {
		return Mage::getModel('catalog/product')->load($_product->getId());
	}

	public function get_opconfig($product) {
		$config = array();
		$_options = $product->getOptions();
        foreach ($_options as $option) {
            /* @var $option Mage_Catalog_Model_Product_Option */
            $priceValue = 0;
            if ($option->getGroupByType() == Mage_Catalog_Model_Product_Option::OPTION_GROUP_SELECT) {
                $_tmpPriceValues = array();
                foreach ($option->getValues() as $value) {
                    /* @var $value Mage_Catalog_Model_Product_Option_Value */
                   $_tmpPriceValues[$value->getId()] = Mage::helper('core')->currency($value->getPrice(true), false, false);
                }
                $priceValue = $_tmpPriceValues;
            } else {
                $priceValue = Mage::helper('core')->currency($option->getPrice(true), false, false);
            }
            $config[$option->getId()] = $priceValue;
        }

        return Mage::helper('core')->jsonEncode($config);
	}

	public function get_options_price($product) {
		$config = array();
        if (!$this->_has_custom_options($product)) {
            return Mage::helper('core')->jsonEncode($config);
        }

        $_request = Mage::getSingleton('tax/calculation')->getRateRequest(false, false, false);
        $_request->setProductClassId($product->getTaxClassId());
        $defaultTax = Mage::getSingleton('tax/calculation')->getRate($_request);

        $_request = Mage::getSingleton('tax/calculation')->getRateRequest();
        $_request->setProductClassId($product->getTaxClassId());
        $currentTax = Mage::getSingleton('tax/calculation')->getRate($_request);

        $_regularPrice = $product->getPrice();
        $_finalPrice = $product->getFinalPrice();
        $_priceInclTax = Mage::helper('tax')->getPrice($product, $_finalPrice, true);
        $_priceExclTax = Mage::helper('tax')->getPrice($product, $_finalPrice);

        $config = array(
            'productId'           => $product->getId(),
            'priceFormat'         => Mage::app()->getLocale()->getJsPriceFormat(),
            'includeTax'          => Mage::helper('tax')->priceIncludesTax() ? 'true' : 'false',
            'showIncludeTax'      => Mage::helper('tax')->displayPriceIncludingTax(),
            'showBothPrices'      => Mage::helper('tax')->displayBothPrices(),
            'productPrice'        => Mage::helper('core')->currency($_finalPrice, false, false),
            'productOldPrice'     => Mage::helper('core')->currency($_regularPrice, false, false),
            'priceInclTax'        => Mage::helper('core')->currency($_priceInclTax, false, false),
            'priceExclTax'        => Mage::helper('core')->currency($_priceExclTax, false, false),
            /**
             * @var skipCalculate
             * @deprecated after 1.5.1.0
             */
            'skipCalculate'       => ($_priceExclTax != $_priceInclTax ? 0 : 1),
            'defaultTax'          => $defaultTax,
            'currentTax'          => $currentTax,
            'idSuffix'            => '_clone',
            'oldPlusDisposition'  => 0,
            'plusDisposition'     => 0,
            'oldMinusDisposition' => 0,
            'minusDisposition'    => 0,
        );

        $responseObject = new Varien_Object();
		Mage::register('current_product', $product);
        Mage::dispatchEvent('catalog_product_view_config', array('response_object'=>$responseObject));
        if (is_array($responseObject->getAdditionalOptions())) {
            foreach ($responseObject->getAdditionalOptions() as $option=>$value) {
                $config[$option] = $value;
            }
        }

        return Mage::helper('core')->jsonEncode($config);
	}
	
	public function get_options($_product) {
		$_result = array();
		
		if ($this->_has_custom_options($_product)) {
			$_result += $this->_get_custom_options($_product);
		}
		
		if ($this->_is_configurable_product($_product)) {
			$_result += $this->_get_configurable_options($_product);
		}
		
		return $_result;
	}
	
	public function get_product_thumbnail($_product, $_width=0, $_height=0) {
		if ($_width == 0) $_width = Mage::helper('ajaxcart')->get_thumbnail_width();
		if ($_height == 0) $_height = Mage::helper('ajaxcart')->get_thumbnail_height();
		$_thumbnail = Mage::helper('catalog/image')
					->init($_product, 'thumbnail')
					->resize($_width, $_height);
    	$_thumbnail = sprintf("%s", $_thumbnail);
		return $_thumbnail;
	}
	
	public function get_random_upsell($_product, $_count = 3) {
		$_upsell = $_product->getUpSellProducts();
		shuffle($_upsell);
		$_result = array(); $i = 0;
		foreach($_upsell as $_product) {
			if ($i++ == $_count) break;
			$_result[] = $this->_reload($_product)->getData();
		}
		return $_result;
	}
}
