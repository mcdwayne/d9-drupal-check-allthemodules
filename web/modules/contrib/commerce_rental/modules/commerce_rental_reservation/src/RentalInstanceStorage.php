<?php

namespace Drupal\commerce_rental_reservation;

use Drupal\commerce\EntityHelper;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines the rental variation storage.
 */
class RentalInstanceStorage extends SqlContentEntityStorage implements RentalInstanceStorageInterface {


  /**
   * {@inheritdoc}
   */
  public function loadMultipleByState($states = [], $variation_id = NULL) {
    $query = $this->getQuery();
    $query->condition('state', $states, 'IN');
    if (isset($variation_id) && $instance_ids = $this->getInstanceIdsByVariationId($variation_id)) {
      $query->condition('instance_id', $instance_ids, 'IN');
    }
    $result = $query->execute();
    if (empty($result)) {
      return [];
    }
    return $this->loadMultiple($result);
  }

  public function getInstanceIdsByVariationId($variation_id) {
    if ($variation_id && $variation = ProductVariation::load($variation_id)) {
      $instances = $variation->get('instances')->referencedEntities();
      $instance_ids = EntityHelper::extractIds($instances);
    }
    return isset($instance_ids) ? $instance_ids : [];
  }

  public function getInstanceIdsOnOrderByVariationId($variation_id) {
    $instances = $this->getInstanceIdsByVariationId($variation_id);
    $query = $this->database->select('commerce_order_item');
    $query->addField('commerce_order_item__instance', 'instance_target_id', 'instance_id');
    $query->addField('commerce_order_item__rental_period', 'rental_period_value', 'start_date');
    $query->addField('commerce_order_item__rental_period', 'rental_period_end_value', 'end_date');
    $query->leftJoin('commerce_order_item__instance', 'commerce_order_item__instance', 'commerce_order_item.order_item_id = commerce_order_item__instance.entity_id');
    $query->leftJoin('commerce_order_item__rental_period', 'commerce_order_item__rental_period', 'commerce_order_item.order_item_id = commerce_order_item__rental_period.entity_id');
    $query->leftJoin('commerce_order', 'commerce_order', 'commerce_order_item.order_id = commerce_order.order_id');
    $query
      ->condition('commerce_order_item__instance.instance_target_id', $instances, 'IN')
      ->condition('commerce_order.state', ['draft', 'fulfillment', 'outed'], 'IN')
      ->condition('commerce_order.cart', 0, '=');

    $result = $query->execute()->fetchAllAssoc('instance_id');

    return $result;

  }

}
