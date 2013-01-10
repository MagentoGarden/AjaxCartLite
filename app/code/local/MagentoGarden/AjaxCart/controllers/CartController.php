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
 * @category    controller
 * @package     magentogarden_ajaxcartlite
 * @copyright   Copyright (c) 2012 MagentoGarden Inc. (http://www.magentogarden.com)
 * @license     http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @version		1.1
 * @author		Alan Marcus (alan.marcus@magentogarden.com);
 */

class MagentoGarden_AjaxCart_CartController extends Mage_Core_Controller_Front_Action {
	
	protected function _get_cart() {
        return Mage::getSingleton('checkout/cart');
    }

    protected function _get_product() {
    	$_session = Mage::getSingleton('ajaxcart/session');
    	return array(
    		'id' => $_session->getData('id'),
    		'name' => $_session->getData('name'),
    		'thumbnail' => $_session->getData('thumbnail'),
    	);
    }
    
    protected function _get_session() {
        return Mage::getSingleton('checkout/session');
    }

    protected function _init_product() {
        $productId = (int) $this->getRequest()->getParam('product');
        if ($productId) {
            $product = Mage::getModel('catalog/product')
                ->setStoreId(Mage::app()->getStore()->getId())
                ->load($productId);
            if ($product->getId()) {
                return $product;
            }
        }
        return false;
    }

    protected function _save_product($_product) {
    	Mage::getSingleton('ajaxcart/session')->setData('name', $_product->getId());
    	Mage::getSingleton('ajaxcart/session')->setData('name', $_product->getName());
    	Mage::getSingleton('ajaxcart/session')->setData('thumbnail', Mage::helper('ajaxcart/product')->get_product_thumbnail($_product));
    }

    /**
	 * 
	 * Get the Count of Items in the shopping cart
	 */
	private function _get_items_count() {
		$_items = $this->_get_session()->getQuote()->getAllItems();
		$_count = 0;
		foreach ($_items as $_item) {
			$_count += $_item->getQty();
			if ($_item['product_type'] == 'configurable') {
				$_count --;
			}
		}
		return $_count;
	}

    private function _get_top_link($_count) {
		if ($_count == 0) {
			return Mage::helper('ajaxcart')->__('My Cart');
		} elseif ($_count == 1) {
			return Mage::helper('ajaxcart')->__('My Cart (%s item)', $_count);
		} else {
			return Mage::helper('ajaxcart')->__('My Cart (%s items)', $_count);
		}
	}
    
	/**
     * 
     * Prepare Return Json Message Structure
     * @param boolean $_success, whether the action is success
     * @param string $_message, message
     * @param array $_data, attached data
     */
	private function _prepare_message_array($_success, $_message, $_data) {
		return array(
			'success' => $_success,
			'message' => $_message,
			'data' => $_data,
		);
	}

    /**
	 * 
	 * Add Product Into Cart
	 */
	public function addAction() {
		try {
			$cart   = $this->_get_cart();
	        $params = $this->getRequest()->getParams();
	        
	        if (isset($params['qty'])) {
	            $filter = new Zend_Filter_LocalizedToNormalized(
	                array('locale' => Mage::app()->getLocale()->getLocaleCode())
	            );
	            $params['qty'] = $filter->filter($params['qty']);
	        }
	        
	        $product = $this->_init_product();
	        $related = $this->getRequest()->getParam('related_product');
	        
	        if (!$product) {
	        	$this->getResponse()->setBody(
	        		json_encode($this->_prepare_message_array(false, 'Product is missing.', null))
	        	);
	        	return false;
	        }
	        
	        $cart->addProduct($product, $params);
	        if (!empty($related)) {
	            $cart->addProductsByIds(explode(',', $related));
	        }
	        
	        $cart->save();
	        $this->_get_session()->setCartWasUpdated(true);
	    } catch (Exception $e) {
	    	$this->getResponse()->setBody(
        		json_encode($this->_prepare_message_array(false, $e->getMessage(), null))
        	);
	    	return false;
	    }
        
        Mage::dispatchEvent('checkout_cart_add_product_complete',
        	array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
        );
            
        $this->_save_product($product);
        $this->getResponse()->setBody(
    		json_encode($this->_prepare_message_array(True, 'Product Successfully Added.', null))
    	);

        return true;
	}
	
	/**
	 * 
	 * After add to cart action, get the info for the added products
	 */
	public function getinfoAction() {
		$_product = $this->_get_product();
		$_count = $this->_get_items_count();
		$_cart = Mage::app()->getLayout()->getBlockSingleton('checkout/cart_sidebar')->setTemplate("checkout/cart/sidebar.phtml")->toHtml();
		
		$_data = array(
			'product' => $_product['id'],
			'name' => $_product['name'],
			'count' => $_count,
			'toplink' => $this->_get_top_link($_count),
			'sidebar' => $_cart,
			'thumbnail' => $_product['thumbnail'],
		);

		$this->getResponse()->setBody(json_encode(
			$this->_prepare_message_array(true, '', $_data)
		));

		return true;
	}
}
