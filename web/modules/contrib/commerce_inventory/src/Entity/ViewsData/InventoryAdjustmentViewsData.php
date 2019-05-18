<?php

namespace Drupal\commerce_inventory\Entity\ViewsData;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Inventory Adjustment entities.
 */
class InventoryAdjustmentViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $base_table = $this->entityType->getBaseTable() ?: $this->entityType->id();

    $data[$base_table]['item_id']['argument']['id'] = 'commerce_inventory_entity_id';

    $data[$base_table]['adjustment_description'] = [
      'field' => [
        'title' => $this->t('Description'),
        'help' => $this->t('Describes the adjustment.'),
        'id' => 'commerce_inventory_adjustment_description',
      ],
    ];

    return $data;
  }

}
