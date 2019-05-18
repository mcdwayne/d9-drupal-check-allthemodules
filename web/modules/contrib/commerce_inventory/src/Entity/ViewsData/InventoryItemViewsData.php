<?php

namespace Drupal\commerce_inventory\Entity\ViewsData;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Inventory Item entities.
 */
class InventoryItemViewsData extends EntityViewsData {

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

    $data[$views_base_table]['location_id']['argument']['id'] = 'commerce_inventory_entity_id';
    $data[$views_base_table]['location_id']['argument']['field entity_type'] = 'commerce_inventory_location';

    $data[$views_base_table]['quantity_available'] = [
      'title' => t('Quantity Available'),
      'help' => t('List the current quantity of available inventory.'),
      'field' => [
        'id' => 'commerce_inventory_item_quantity_available',
      ],
    ];

    $data[$views_base_table]['quantity_on_hand'] = [
      'title' => t('Quantity On-Hand'),
      'help' => t('List the current quantity of on-hand inventory.'),
      'field' => [
        'id' => 'commerce_inventory_item_quantity_on_hand',
      ],
    ];

    $data[$views_base_table]['provider_configuration_status'] = [
      'title' => t('Provider Configuration Status'),
      'help' => t("A boolean indicating the inventory item's provider-configuration status."),
      'field' => [
        'id' => 'commerce_inventory_item_provider_configuration_status',
      ],
    ];

    return $data;
  }

}
