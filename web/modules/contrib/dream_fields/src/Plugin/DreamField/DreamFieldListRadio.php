<?php

namespace Drupal\dream_fields\Plugin\DreamField;

use Drupal\dream_fields\FieldBuilderInterface;

/**
 * Plugin implementation of 'list_radio'.
 *
 * @DreamField(
 *   id = "list_radio",
 *   label = @Translation("List of radio items"),
 *   description = @Translation("This will add a list of radio buttons and it will be outputted it with the label at the top."),
 *   preview = "images/radios-dreamfields.png",
 *   preview_provider = "dream_fields",
 *   provider = "options",
 *   field_types = {
 *     "list_string"
 *   },
 * )
 */
class DreamFieldListRadio extends DreamFieldList {

  /**
   * {@inheritdoc}
   */
  public function saveForm($values, FieldBuilderInterface $field_builder) {
    $field_builder
      ->setWidget('options_buttons')
      ->setCardinality(1);
    parent::saveForm($values, $field_builder);
  }

}
