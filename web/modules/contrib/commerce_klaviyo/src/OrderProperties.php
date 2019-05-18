<?php

namespace Drupal\commerce_klaviyo;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * The Klaviyo Order Properties object.
 *
 * @package Drupal\commerce_klaviyo
 */
class OrderProperties extends KlaviyoPropertiesBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityInterface $source_entity) {
    parent::__construct($config_factory, $source_entity);
    $this->assertEntity($source_entity);

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $source_entity;
    $this->properties['$event_id'] = $order->id();

    if ($order->getTotalPrice() != NULL) {
      $this->properties['$value'] = $order->getTotalPrice()->getNumber();
    }

    $categories = $brands = [];

    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if (!$purchased_entity) {
        // Skip if purchased product does not exist.
        continue;
      }
      $item_names[] = $purchased_entity->label();
      $item = [
        'ProductName' => $purchased_entity->label(),
        'Quantity' => $order_item->getQuantity(),
        'ItemPrice' => $purchased_entity->getPrice()->getNumber(),
        'RowTotal' => $order_item->getTotalPrice()->getNumber(),
      ];

      if ($purchased_entity instanceof ProductVariationInterface) {
        $product = $purchased_entity->getProduct();
        $product_properties = new ProductProperties($this->configFactory, $product);
        $product_properties = $product_properties->getProperties();

        if (isset($product_properties['Price'])) {
          unset($product_properties['Price']);
        }

        $item += $product_properties;
        $item['SKU'] = $purchased_entity->getSku();
      }

      if (!empty($item['Categories'])) {
        $categories = array_merge($categories, $item['Categories']);
      }
      if (!empty($item['Brand'])) {
        $brands[] = $item['Brand'];
      }

      $this->properties['Items'][] = $item;
    }

    if (isset($item_names)) {
      $this->properties['ItemNames'] = $item_names;
    }
    if (!empty($categories)) {
      $this->properties['Categories'] = array_unique($categories);
    }
    if (!empty($brands)) {
      $this->properties['Brands'] = array_unique($brands);
    }
  }

  /**
   * Gets the properties array formatted for the "Ordered product" track call.
   *
   * @return array
   *   The array of properties.
   */
  public function getOrderedProductProperties() {
    $order_items = [];
    $properties = $this->getProperties();

    foreach ($properties['Items'] as $item) {
      $order_item = [
        '$event_id' => isset($item['SKU']) ? $properties['$event_id'] . '_' . $item['SKU'] : $properties['$event_id'],
        'RowTotal' => $properties['$value'],
      ] + $item;
      $order_items[] = $order_item;
    }

    return $order_items;
  }

  /**
   * Asserts that the given entity is an order.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  protected function assertEntity(EntityInterface $entity) {
    if ($entity->getEntityTypeId() != 'commerce_order') {
      throw new \InvalidArgumentException(sprintf('The OrderProperties a "commerce_order" entity, but a "%s" entity was given.', $entity->getEntityTypeId()));
    }
  }

}
