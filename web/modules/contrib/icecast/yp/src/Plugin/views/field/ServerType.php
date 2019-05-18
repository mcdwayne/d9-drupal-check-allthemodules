<?php

namespace Drupal\yp\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Render stream type as an abbreviation.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("yp_server_type")
 */
class ServerType extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $value = $values->{$this->field_alias};
    switch ($value) {
      case 'application/ogg':
      case 'Ogg Vorbis':
        $value = 'Ogg';
        break;

      case 'audio/mpeg':
      case 'MP3 audio':
      case 'application/mp3':
        $value = 'MP3';
        break;

      case 'audio/aacp':
        $value = 'AAC+';
        break;
    }
    return $this->sanitizeValue($value);
  }

}
