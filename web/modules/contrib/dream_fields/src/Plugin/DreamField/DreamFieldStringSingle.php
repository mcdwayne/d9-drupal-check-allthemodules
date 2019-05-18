<?php

namespace Drupal\dream_fields\Plugin\DreamField;

use Drupal\dream_fields\DreamFieldPluginBase;
use Drupal\dream_fields\FieldBuilderInterface;

/**
 * Plugin implementation of 'text_single'.
 *
 * @DreamField(
 *   id = "text_single",
 *   label = @Translation("Single line of text"),
 *   description = @Translation("This will add an input field for a single line of text and will be outputted with the label in the front."),
 *   weight = -10,
 *   preview = "images/textfield-dreamfields.png",
 *   preview_provider = "dream_fields",
 *   provider = "text",
 *   field_types = {
 *     "string"
 *   },
 * )
 */
class DreamFieldStringSingle extends DreamFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function saveForm($values, FieldBuilderInterface $field_builder) {
    $field_builder
      ->setField('string')
      ->setWidget('string_textfield')
      ->setDisplay('text_default', [], 'inline');
  }

}
