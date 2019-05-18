<?php

namespace Drupal\dream_fields\Plugin\DreamField;

use Drupal\dream_fields\FieldBuilderInterface;

/**
 * Plugin implementation of 'list_select'.
 *
 * @DreamField(
 *   id = "list_select",
 *   label = @Translation("Dropdown list"),
 *   description = @Translation("This will add a dropdown list and it will be outputted with the label in the front."),
 *   preview = "images/selectbox-dreamfields.png",
 *   preview_provider = "dream_fields",
 *   provider = "options",
 *   field_types = {
 *     "list_string"
 *   },
 * )
 */
class DreamFieldListSelect extends DreamFieldList {

  /**
   * {@inheritdoc}
   */
  public function saveForm($values, FieldBuilderInterface $field_builder) {
    $field_builder
      ->setWidget('options_select')
      ->setCardinality(1);
    parent::saveForm($values, $field_builder);
  }

}
