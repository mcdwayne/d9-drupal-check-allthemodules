<?php

namespace Drupal\commerce_inventory\Plugin\views\field;

use Drupal\views\Plugin\views\field\Boolean;
use Drupal\views\ResultRow;

/**
 * Field handler to present the validity of an Inventory Item's provider config.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_inventory_item_provider_configuration_status")
 */
class CommerceInventoryItemProviderConfigurationStatus extends Boolean {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $values, $field = NULL) {
    /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $entity */
    $entity = $this->getEntity($values);
    return $entity->isValid();
  }

}
