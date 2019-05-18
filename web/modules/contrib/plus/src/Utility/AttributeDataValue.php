<?php

namespace Drupal\plus\Utility;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;

/**
 * A class that defines a type of data HTML attribute.
 */
class AttributeDataValue extends AttributeBase {

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    $value = $this->value();

    // Handle null based data attributes.
    if ($value === NULL) {
      return 'null';
    }

    // Handle boolean based data attributes.
    if ($value === TRUE || $value === FALSE) {
      return $value ? 'true' : 'false';
    }

    if (is_string($value)) {
      // Decode HTML entities (in case data attribute was already JSON encoded).
      $value = Html::decodeEntities($value);

      // Replace leading and trailing quotes if string was already JSON encoded.
      if (preg_match('/^"[\w]+"$/', $value)) {
        $value = preg_replace('/^"|"$/', '', $value);
      }

      return Html::escape($value);
    }
    elseif (is_numeric($value)) {
      return Html::escape($value);
    }

    // At this point, value should always be an array. If it isn't, return "".
    if (!is_array($value)) {
      return '';
    }

    return Html::escape(Json::encode($value));
  }

}
