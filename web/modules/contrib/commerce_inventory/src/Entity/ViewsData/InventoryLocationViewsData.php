<?php

namespace Drupal\commerce_inventory\Entity\ViewsData;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Inventory Location entities.
 */
class InventoryLocationViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $base_table = $this->entityType->getBaseTable() ?: $this->entityType->id();
    $base_field = $this->entityType->getKey('id');

    $translatable = $this->entityType->isTranslatable();
    $data_table = '';
    if ($translatable) {
      $data_table = $this->entityType->getDataTable() ?: $this->entityType->id() . '_field_data';
    }

    $views_base_table = $base_table;
    if ($data_table) {
      $views_base_table = $data_table;
    }

    $data[$views_base_table][$base_field]['argument']['id'] = 'commerce_inventory_entity_id';

    return $data;
  }

}
