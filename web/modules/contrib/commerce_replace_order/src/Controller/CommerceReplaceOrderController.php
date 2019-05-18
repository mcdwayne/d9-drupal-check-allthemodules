<?php

namespace Drupal\commerce_replace_order\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Allows User To create a new order that contains the same order line items 
 * as a previous order
 */
class CommerceReplaceOrderController extends ControllerBase {

/**
 * returns the Module Name
 */
  protected function getModuleName() {
    return 'commerce_replace_order';
  }

 /**
 * Replace the whole Order
 */
  public function commerce_replace_order_replace_order ($order_id = NULL) {
    $this->order_id = $order_id;
    $order = \Drupal::entityTypeManager()
    ->getStorage('commerce_order')
    ->load($this->order_id);

    if($order->getCustomerId() == \Drupal::currentUser()->id()){
      $store_id = $order->getStoreId();
      $order_type = $order->get('type')->getValue()[0]['target_id']; 
      
      $order_details = \Drupal::entityTypeManager()
      ->getStorage('commerce_order_item')
      ->loadByProperties(['order_id' => $this->order_id]);

      //Fetching Quantity, Title and Purchased Entity of the order.

      foreach ($order_details as $order_detail) {
        $variant_id = $order_detail
        ->get('purchased_entity')
        ->getValue()[0]['target_id'];
        $quantity = $order_detail->getQuantity();
        $title = $order_detail->getTitle();
        $response = $this->commerce_replace_order_reorder($store_id, 
          $variant_id, $quantity, $order_type, $title);
      }

    }
    else {
      drupal_set_message($this->t('You are not authorized to access this 
        order.'), 'error');
      $response = new RedirectResponse('/user/' 
        . \Drupal::currentUser()->id() . '/orders');
    }

    return $response;
  }

/**
 * Replace a Product of an order
 */
  public function commerce_replace_order_replace_product ($order_id = NULL, $order_item_id = NULL) {
    $this->order_item_id = $order_item_id;
    $this->order_id = $order_id;

    $order = \Drupal::entityTypeManager()
    ->getStorage('commerce_order')
    ->load($this->order_id);

    if($order->getCustomerId() == \Drupal::currentUser()->id()){
      $store_id = $order->getStoreId();
      $order_type = $order->get('type')->getValue()[0]['target_id']; 

      $order_details = \Drupal::entityTypeManager()
      ->getStorage('commerce_order_item')
      ->load($this->order_item_id);

      if (!(empty($order_details))) {
        $variant_id = $order_details
        ->get('purchased_entity')
        ->getValue()[0]['target_id'];
        $title = $order_details->getTitle();
        $quantity = $order_details->getQuantity();
        $response = $this->commerce_replace_order_reorder($store_id, 
          $variant_id, $quantity, $order_type, $title);
      }
      else {
        drupal_set_message($this->t('Product is currently not active.'),
         'warning');
      $response = new RedirectResponse('/user/' 
        . \Drupal::currentUser()->id() . '/orders');
      }
    }

    else {
      drupal_set_message($this->t('You are not authorized to access this 
        order.'), 'error');
      $response = new RedirectResponse('/user/' 
        . \Drupal::currentUser()->id() . '/orders');
    }

    return $response;	

  }

/**
 * Adds the product to the cart
 */
  function commerce_replace_order_reorder ($store_id, $variation_id, $quantity, $order_type, $title){
    $entity_manager = \Drupal::entityTypeManager();
    $cart_manager = \Drupal::service('commerce_cart.cart_manager');
    $cart_provider = \Drupal::service('commerce_cart.cart_provider');
    $store = $entity_manager->getStorage('commerce_store')->load($store_id); 
    $product_variation = $entity_manager
      ->getStorage('commerce_product_variation')
      ->load($variation_id);

    if (isset($product_variation)){
      $cart = $cart_provider->getCart($order_type, $store);

      if (!$cart) {
        $cart = $cart_provider->createCart($order_type, $store);
      }

      $order_item = $entity_manager->getStorage('commerce_order_item')
      ->create([
        'type' => 'default',
        'purchased_entity' => (string) $variation_id,
        'quantity' => $quantity,
        'unit_price' => $product_variation->getPrice(),
      ]);
      $order_item->save();

      if($cart_manager->addOrderItem($cart, $order_item)){
        $response = new RedirectResponse('/cart');
      }

      else{
        drupal_set_message($this->t('<strong>' . $title 
          . '</strong> is currently not active.'), 'warning');
        $response = new RedirectResponse('/user/' 
          . \Drupal::currentUser()->id() . '/orders');
      }

    }

    else {
      drupal_set_message($this->t('<strong>' . $title 
        . '</strong> is Currently Out of Stock'), 'warning');

      /**
       * Sends mail to Admin if The product is currently not Available.
       */
      if(\Drupal::config('commerce_replace_order.config')->get('checkbox_mail') == '1') {
        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $userid = $user->get('uid')->value;
        $username = $user->getUsername();
        $mailManager = \Drupal::service('plugin.manager.mail');
        $module = $this->getModuleName();
        $key = $title . ' is Currently Out of Stock';
        $to = \Drupal::config('commerce_replace_order.config')->get('email');
        $params['subject'] = "A User has Wished to reorder Your Product "
         . $title;
        $params['message'] = $username . ' With User Id ' . $userid 
          . ' has Looked out for Your Product ' . $title . ' With Product Id ' 
          . $variation_id . '.';
        $params['node_title'] = $title;
        $langcode = \Drupal::currentUser()->getPreferredLangcode();
        $send = TRUE;
        $result = $mailManager->mail($module, $key, $to, $langcode, $params,
         NULL, $send);
      }
      $response = new RedirectResponse('/user/' 
					. \Drupal::currentUser()->id() . '/orders');
    }
    return $response;
  }
}