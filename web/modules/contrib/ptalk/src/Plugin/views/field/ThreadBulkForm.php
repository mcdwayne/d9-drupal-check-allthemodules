<?php

namespace Drupal\ptalk\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a thread operations bulk form element.
 *
 * @ViewsField("ptalk_thread_bulk_form")
 */
class ThreadBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('Select one or more conversations to perform the update on.');
  }

}
