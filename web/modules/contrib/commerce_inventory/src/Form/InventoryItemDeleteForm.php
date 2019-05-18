<?php

namespace Drupal\commerce_inventory\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides a form for deleting Inventory Item entities.
 *
 * @ingroup commerce_inventory
 */
class InventoryItemDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->getLocation()->toUrl('inventory');
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getEntity()->getLocation()->toUrl('inventory');
  }

}
