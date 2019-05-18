<?php

namespace Drupal\httpbl\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a host operations bulk form element.
 *
 * @ViewsField("host_bulk_form")
 */
class HostBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No hosts selected.');
  }

}
