<?php

namespace Drupal\cloudwords\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler which shows if an asset has been added to the current project.
 *
 * @ViewsField("cloudwords_translatable_added_to_project_field")
 */
class AddedToProject extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $this->getValue($values);

    return $value;
  }

}
