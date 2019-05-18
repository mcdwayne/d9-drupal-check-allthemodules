<?php

namespace Drupal\yp\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Render "unknown" current song as empty string.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("yp_current_song")
 */
class CurrentSong extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $values->{$this->field_alias};
    $value = ($value == 'unknown - unknown') ? '' : $value;
    return $this->sanitizeValue($value);
  }

}
