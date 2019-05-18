<?php

namespace Drupal\yp\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Render stream bitrate with a "k" for kbps.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("yp_bitrate")
 */
class Bitrate extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $values->{$this->field_alias};
    $value = $value ? $value . 'k' : t('VBR');
    // Integer field does not require check_plain().
    return $value;
  }

}
