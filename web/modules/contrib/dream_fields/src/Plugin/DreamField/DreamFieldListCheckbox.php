<?php

namespace Drupal\dream_fields\Plugin\DreamField;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\dream_fields\FieldBuilderInterface;

/**
 * Plugin implementation of 'list_checkbox'.
 *
 * @DreamField(
 *   id = "list_checkbox",
 *   label = @Translation("List of checkboxes"),
 *   description = @Translation("This will add a list of checkboxes and will be outputted with the label at the top."),
 *   preview = "images/checkboxes-dreamfields.png",
 *   preview_provider = "dream_fields",
 *   provider = "options",
 *   field_types = {
 *     "list_string"
 *   },
 * )
 */
class DreamFieldListCheckbox extends DreamFieldList {

  /**
   * {@inheritdoc}
   */
  public function saveForm($values, FieldBuilderInterface $field_builder) {
    $field_builder
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setWidget('options_buttons');
    parent::saveForm($values, $field_builder);
  }

}
