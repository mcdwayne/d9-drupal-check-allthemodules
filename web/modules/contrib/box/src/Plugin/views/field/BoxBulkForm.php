<?php

namespace Drupal\box\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a box operations bulk form element.
 *
 * @ViewsField("box_bulk_form")
 */
class BoxBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No box selected.');
  }

}
