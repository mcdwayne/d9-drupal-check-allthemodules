<?php

namespace Drupal\merci_line_item\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a node operations bulk form element.
 *
 * @ViewsField("merci_line_item_bulk_form")
 */
class MerciLineItemBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No content selected.');
  }

}


