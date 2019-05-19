<?php

namespace Drupal\webform_cart;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\Url;
use Drupal\webform_cart\Form\UpdateCartForm;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class WebformCart.
 */
class WebformCart implements WebformCartInterface {

  use UrlGeneratorTrait;

  private $cart;

  private $webformCartSession;

  private $entityTypeManager;

  private $entityFieldManager;

  private $destination;


  /**
   * WebformCart constructor.
   *
   * @param \Drupal\webform_cart\WebformCartSessionInterface $webform_cart_session
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   */
  public function __construct(WebformCartSessionInterface $webform_cart_session,
                              EntityTypeManagerInterface $entity_type_manager,
                              EntityFieldManagerInterface $entity_field_manager) {
    $this->webformCartSession = $webform_cart_session;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * @return mixed
   */
  public function getCart() {
    return $this->cart;
  }

  public function getCount() {
    return $this->cartCount();
  }

  public function setDestination($destination) {
    $this->destination = $destination;
  }

  /**
   * @param $orderItem
   *
   * @return int|mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setCart($orderItem) {
    $cartId = $this->webformCartSession->getCartIds();

    // If CartId exists, load existing order.
    if ($cartId) {
      // Loop helps clean up any bad CartIds.
      foreach ($cartId as $value) {
        $orderEntity = $this->entityTypeManager->getStorage('webform_cart_order')
          ->load($value);
        // If cartId exists but order doesn't clear existing order id from session
        // To prevent multiple cartIds stored.
        if (!$orderEntity) {
          $this->webformCartSession->deleteCartId($value);
        }
      }
      $cartId = $this->webformCartSession->getCartIds();
    }

    // If no CartId or Order exists create new.
    if (empty($cartId) && !isset($orderEntity)) {
      $orderEntity = $this->entityTypeManager
        ->getStorage('webform_cart_order')
        ->create(['type' => $orderItem['order_type']]);
      $orderEntity->save();
      $this->webformCartSession->addCartId($orderEntity->id());
    }
    // Create Line for Order.
    $orderItemEntity = $this->addLineItem($orderEntity, $orderItem);
    // Add lineitem ID to Order Reference field.
    $orderEntity->field_order_item[] = $orderItemEntity->id();
    $orderEntity->save();
    $orderLineItems = $orderEntity->get('field_order_item')->getValue();
    $cartCount = count($orderLineItems);

    return $cartCount;
  }

  /**
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCheckout() {
    $orders = 'Order is empty';
    $webform_build = 'No form to complete';
    $cartId = $this->webformCartSession->getCartIds();
    if ($cartId) {
      $orderEntity = $this->entityTypeManager->getStorage('webform_cart_order')->load($cartId[0]);

      if ($orderEntity) {
        $targetWebform = $orderEntity->get('field_webform')->getValue();
        if ($targetWebform) {
          $webform_load = \Drupal\webform\Entity\Webform::load($targetWebform[0]['target_id']);
          $webform_build = \Drupal::entityTypeManager()
            ->getViewBuilder('webform')
            ->view($webform_load);
        }

        $orderLineIds = [];
        $orderLineItems = $orderEntity->get('field_order_item')->getValue();
        foreach ($orderLineItems as $key => $value) {
          $orderLineIds[$key] = $value['target_id'];
        }
        $orders = [];
        foreach ($orderLineIds as $key => $orderLineId) {
          $orderItemEntity = $this->entityTypeManager->getStorage('webform_cart_item')->load($orderLineId);
          $view_builder = $this->entityTypeManager->getViewBuilder('webform_cart_item');
          $orders[$key]['order'] = $view_builder->view($orderItemEntity);
          $orders[$key]['form'] = $this->lineItemForm($key, $orderItemEntity);
        }
      }
    }

    return [
      '#theme' => 'webform_cart_checkout',
      '#order' => $orders,
      '#webform' => $webform_build,
      '#attached' => [
        'library' => [
          'webform_cart/webform_cart-update',
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ]
    ];
  }

  /**
   * @param $itemId
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeItem($itemId) {

    $cartId = $this->webformCartSession->getCartIds();

    // If CartId exists, load existing order.
    if ($cartId) {
      // Loop helps clean up any bad CartIds.
      foreach ($cartId as $value) {
        $orderEntity = $this->entityTypeManager->getStorage('webform_cart_order')
          ->load($value);
        // If cartId exists but order doesn't clear existing order id from session
        // To prevent multiple cartIds stored.
        if (!$orderEntity) {
          $this->webformCartSession->deleteCartId($value);
        }
      }

      // Updated order entity lineitem reference.
      if ($orderEntity) {
        $orderLineIds = [];
        $orderLineItems = $orderEntity->get('field_order_item')->getValue();
        foreach ($orderLineItems as $key => $value) {
          if ($value['target_id'] != $itemId) {
            $orderLineIds[$key] = $value['target_id'];
          }
        }
        $orderEntity->field_order_item = $orderLineIds;
        $orderEntity->save();
      }

      // Delete line item.
      if ($itemId) {
        $orderItemEntity = $this->entityTypeManager->getStorage('webform_cart_item')->load($itemId);
        if ($orderItemEntity) {
          $orderItemEntity->delete();
        }
      }
    }

    if (isset($this->destination)) {
      $destination = $this->destination;
    }
    else {
      $destination = '<front>';
    }

    $response = new RedirectResponse($destination);
    $response->send();
    return;
  }

  /**
   * @param $orderItem
   *
   * @return mixed|void
   */
  public function updateQuantity($orderItem) {

    if (isset($orderItem)) {
      $lineItem = $this->entityTypeManager
        ->getStorage('webform_cart_item')
        ->load($orderItem['entity_id']);
      $lineItem->set('quantity', $orderItem['quantity']);
      $lineItem->save();
    }
    $quantity = $lineItem->quantity->value;
    return $quantity;
  }

  /**
   * @param $orderEntity
   * @param $orderItem
   *
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function addLineItem($orderEntity, $orderItem) {

    $bundle_fields = $this->entityFieldManager
      ->getFieldDefinitions('webform_cart_order', $orderItem['order_type']);
    $field_name = 'field_order_item';
    $field_definition = $bundle_fields[$field_name];
    $target_bundle = $field_definition->getSettings()['handler_settings']['target_bundles'];
    foreach ($target_bundle as $value) {
      $orderItemEntity = $this->entityTypeManager
        ->getStorage('webform_cart_item')
        ->create(['type' => $value]);
      $name = $this->entityTypeManager->getStorage('node')->load($orderItem['node_id']);
      $orderItemEntity->set('name',$name->label());
      $orderItemEntity->set('order_id', $orderEntity->id());
      $orderItemEntity->set('original_product', $orderItem['node_id']);
      $orderItemEntity->set('quantity', $orderItem['quantity']);
      $orderItemEntity->set('quantitySetting', $orderItem['quantitySetting']);
      $orderItemEntity->set('data1', $orderItem['data1']);
      $orderItemEntity->set('data2', $orderItem['data2']);
      $orderItemEntity->save();
      break;
    }

    return $orderItemEntity;
  }

  /**
   * @return int
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function cartCount() {
    $cartId = $this->webformCartSession->getCartIds();
    $count = 0;
    if ($cartId) {
      $orderEntity = $this->entityTypeManager->getStorage('webform_cart_order')
        ->load($cartId[0]);
      if ($orderEntity) {
        $orderLineItems = $orderEntity->get('field_order_item')->getValue();
        $count = count($orderLineItems);
      }
    }
    return $count;
  }

  private function lineItemForm($index, $entity) {
    $quantity = (isset($entity->quantity->value) ? $entity->quantity->value : NULL);
    $quantitySetting = (isset($entity->quantitySetting->value) ? $entity->quantitySetting->value : NULL);
    $form_pre = new UpdateCartForm($this);
    $form_pre->setFormId('-' . $index . '-' . $entity->id());
    return \Drupal::formBuilder()->getForm($form_pre, $entity->id(), $quantitySetting, $quantity);
  }

}
