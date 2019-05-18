<?php

namespace Drupal\legal\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("unserialized_list")
 */
class UnserializedListField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $extras = unserialize($values->{$this->field_alias});

    // Remove extras without value.
    $this->removeEmpty($extras);

    return [
      '#theme' => 'item_list',
      '#items' => $extras,
    ];
  }

  /**
   * Removes all items without value from the extras array.
   *
   * @param array $extras
   *   The array of extras.
   */
  private function removeEmpty(array &$extras) {
    foreach ($extras as $key => $value) {
      if ($value == '') {
        unset($extras[$key]);
      }
    }
  }

}
