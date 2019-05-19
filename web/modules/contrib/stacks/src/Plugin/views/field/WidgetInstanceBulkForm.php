<?php

namespace Drupal\stacks\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;

/**
 * Defines a node operations bulk form element.
 *
 * @ViewsField("widget_instance_bulk_form")
 */
class WidgetInstanceBulkForm extends BulkForm {

  /**
   * {@inheritdoc}
   */
  protected function emptySelectedMessage() {
    return t('No content selected.');
  }

}
