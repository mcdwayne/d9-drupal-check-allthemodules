<?php

namespace Drupal\cloudwords\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler which shows the translation status for translatables.
 *
 * @ViewsField("cloudwords_project_status_field")
 */
class ProjectStatus extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $opts = array_merge(cloudwords_project_active_options_list(), cloudwords_project_closed_options_list());
    $val = $this->getValue($values);
    return isset($opts[$val]) ? $opts[$val] : $val;
  }

}
