<?php

namespace Drupal\cacheflush_ui\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a cacheflush operations bulk form element.
 *
 * @ViewsField("cacheflush_bulk_form")
 */
class CacheflushBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return $this->t('No content selected.');
  }

}
