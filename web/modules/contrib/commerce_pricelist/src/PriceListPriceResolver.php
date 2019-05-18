<?php

namespace Drupal\commerce_pricelist;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_price\Resolver\PriceResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class PriceListPriceResolver implements PriceResolverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A static cache of loaded price list items.
   *
   * @var array
   */
  protected $priceListItems = [];

  /**
   * A static cache of loaded price list IDs.
   *
   * @var array
   */
  protected $priceListIds = [];

  /**
   * Constructs a new PriceListPriceResolver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    $price = NULL;
    $price_list_item = $this->loadPriceListItem($entity, $quantity, $context);
    if ($price_list_item) {
      $field_name = $context->getData('field_name', 'price');
      if ($field_name == 'list_price') {
        $price = $price_list_item->getListPrice();
      }
      elseif ($field_name == 'price') {
        $price = $price_list_item->getPrice();
      }
    }

    return $price;
  }

  /**
   * Loads the price list item for the given purchasable entity.
   *
   * @param \Drupal\commerce\PurchasableEntityInterface $entity
   *   The purchasable entity.
   * @param int $quantity
   *   The quantity.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return \Drupal\commerce_pricelist\Entity\PriceListItemInterface
   *   The price list item.
   */
  protected function loadPriceListItem(PurchasableEntityInterface $entity, $quantity, Context $context) {
    $customer_id = $context->getCustomer()->id();
    $store_id = $context->getStore()->id();
    $today = gmdate('Y-m-d', $context->getTime());
    $cache_key = implode(':', [$entity->id(), $quantity, $customer_id, $store_id, $today]);
    if (array_key_exists($cache_key, $this->priceListItems)) {
      return $this->priceListItems[$cache_key];
    }

    $price_list_ids = $this->loadPriceListIds($entity->getEntityTypeId(), $context);
    if (empty($price_list_ids)) {
      $this->priceListItems[$cache_key] = NULL;
      return NULL;
    }

    $price_list_item = NULL;
    $price_list_item_storage = $this->entityTypeManager->getStorage('commerce_pricelist_item');
    $query = $price_list_item_storage->getQuery();
    $query
      ->condition('type', $entity->getEntityTypeId())
      ->condition('price_list_id', $price_list_ids, 'IN')
      ->condition('quantity', $quantity, '<=')
      ->condition('purchasable_entity', $entity->id())
      ->condition('status', TRUE)
      ->sort('quantity', 'ASC');
    $result = $query->execute();
    if (!empty($result)) {
      $price_list_items = $price_list_item_storage->loadMultiple($result);
      $price_list_item = $this->selectPriceListItem($price_list_items, $price_list_ids);
    }
    $this->priceListItems[$cache_key] = $price_list_item;

    return $price_list_item;
  }

  /**
   * Selects the best matching price list item, based on quantity and weight.
   *
   * Assumes that price list items are ordered by quantity, and that
   * price list IDs are ordered by weight.
   *
   * @param \Drupal\commerce_pricelist\Entity\PriceListItemInterface[] $price_list_items
   *   The price list items.
   * @param int[] $price_list_ids
   *   The price list IDs.
   *
   * @return \Drupal\commerce_pricelist\Entity\PriceListItemInterface
   *   The selected price list item.
   */
  protected function selectPriceListItem(array $price_list_items, array $price_list_ids) {
    if (count($price_list_items) > 1) {
      // Multiple matching price list items found.
      // First, reduce to one per price list, by selecting the quantity tier.
      $grouped_price_list_items = [];
      foreach ($price_list_items as $price_list_item) {
        $price_list_id = $price_list_item->getPriceListId();
        $grouped_price_list_items[$price_list_id] = $price_list_item;
      }
      // Then, select the one whose price list has the smallest weight.
      $price_list_weights = [];
      foreach ($grouped_price_list_items as $price_list_id => $price_list_item) {
        $price_list_weight = array_search($price_list_id, $price_list_ids);
        $price_list_weights[$price_list_id] = $price_list_weight;
      }
      asort($price_list_weights);
      $sorted_price_list_ids = array_keys($price_list_weights);
      $price_list_id = reset($sorted_price_list_ids);
      $price_list_item = $grouped_price_list_items[$price_list_id];
    }
    else {
      $price_list_item = reset($price_list_items);
    }

    return $price_list_item;
  }

  /**
   * Loads the available price list IDs for the given bundle and context.
   *
   * @param string $bundle
   *   The price list bundle.
   * @param \Drupal\commerce\Context $context
   *   The context.
   *
   * @return int[]
   *   The price list IDs.
   */
  protected function loadPriceListIds($bundle, Context $context) {
    $customer_id = $context->getCustomer()->id();
    $store_id = $context->getStore()->id();
    $today = gmdate('Y-m-d', $context->getTime());
    $cache_key = implode(':', [$bundle, $customer_id, $store_id, $today]);
    if (array_key_exists($cache_key, $this->priceListIds)) {
      return $this->priceListIds[$cache_key];
    }

    $price_list_storage = $this->entityTypeManager->getStorage('commerce_pricelist');
    $query = $price_list_storage->getQuery();
    $query
      ->condition('type', $bundle)
      ->condition('stores', [$store_id], 'IN')
      ->condition($query->orConditionGroup()
        ->condition('customer', $customer_id)
        ->notExists('customer')
      )
      ->condition($query->orConditionGroup()
        ->condition('customer_roles', $context->getCustomer()->getRoles(), 'IN')
        ->notExists('customer_roles')
      )
      ->condition('start_date', $today, '<=')
      ->condition($query->orConditionGroup()
        ->condition('end_date', $today, '>=')
        ->notExists('end_date')
      )
      ->condition('status', TRUE)
      ->sort('weight', 'ASC')
      ->sort('id', 'DESC');
    $result = $query->execute();
    $price_list_ids = array_values($result);
    $this->priceListIds[$cache_key] = $price_list_ids;

    return $price_list_ids;
  }

}
