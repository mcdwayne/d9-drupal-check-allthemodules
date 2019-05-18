<?php

namespace Drupal\commerce_inventory\Form;

use Drupal\core_extend\Form\EntityEditMultipleForm;

/**
 * Provides a inventory creation confirmation form.
 */
class InventoryItemEditMultipleForm extends EntityEditMultipleForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_inventory_item_edit_multiple_at_location';
  }

}
