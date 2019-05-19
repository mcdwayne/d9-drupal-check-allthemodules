<?php

namespace Drupal\visualn_iframe\Plugin\Field\FieldType;

use Drupal\text\Plugin\Field\FieldType\TextItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

// @todo: add 'category' annotation field (though with no_ui not practically needed)

/**
 * Plugin implementation of the 'visualn_iframe_data' field type.
 *
 * @FieldType(
 *   id = "visualn_iframe_data",
 *   label = @Translation("VisualN IFrame data"),
 *   description = @Translation("Stored data used to generate iframe content"),
 *   default_widget = "visualn_iframe_data",
 *   default_formatter = "visualn_iframe_data",
 *   no_ui = "TRUE"
 * )
 */
class IFrameDataItem extends TextItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'big',
        ],
      ],
    ];
  }

}
