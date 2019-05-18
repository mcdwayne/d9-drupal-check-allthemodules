<?php

namespace Drupal\legal\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("exploded_list")
 */
class ExplodedListField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $changes = explode("\r\n", $values->{$this->field_alias});
    $build   = array(
      '#theme' => 'item_list',
      '#items' => $changes,
    );
    return $build;
  }

}
