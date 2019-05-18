<?php

namespace Drupal\key_value_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;

/**
 * Plugin implementation of the 'key_value' widget.
 *
 * @FieldWidget(
 *   id = "key_value_textfield",
 *   label = @Translation("Key / Value"),
 *   field_types = {
 *     "key_value"
 *   }
 * )
 */
class KeyValueTextfieldWidget extends StringTextfieldWidget {

  // Add overrides from the common trait.
  use KeyValueWidgetTrait;

}
