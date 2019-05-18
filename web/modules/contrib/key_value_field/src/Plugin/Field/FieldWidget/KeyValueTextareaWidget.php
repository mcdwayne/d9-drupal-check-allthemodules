<?php

namespace Drupal\key_value_field\Plugin\Field\FieldWidget;

use Drupal\text\Plugin\Field\FieldWidget\TextareaWidget;

/**
 * Plugin implementation of the 'key_value' widget.
 *
 * @FieldWidget(
 *   id = "key_value_textarea",
 *   label = @Translation("Key / Value (multiple rows)"),
 *   field_types = {
 *     "key_value_long"
 *   }
 * )
 */
class KeyValueTextareaWidget extends TextareaWidget {

  // Add overrides from the common trait.
  use KeyValueWidgetTrait;

}
