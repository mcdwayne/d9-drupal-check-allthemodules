<?php

namespace Drupal\dream_fields\Plugin\DreamField;

use Drupal\dream_fields\DreamFieldPluginBase;
use Drupal\dream_fields\FieldBuilderInterface;

/**
 * Plugin implementation of 'text_long'.
 *
 * @DreamField(
 *   id = "text_long",
 *   label = @Translation("Multiples lines of text"),
 *   description = @Translation("This will add an input field for multiple lines of text and will be outputted with the label at the top."),
 *   weight = -9,
 *   preview = "images/textarea-dreamfields.png",
 *   preview_provider = "dream_fields",
 *   provider = "text",
 *   field_types = {
 *     "text_long"
 *   },
 * )
 */
class DreamFieldStringMulti extends DreamFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function saveForm($values, FieldBuilderInterface $field_builder) {
    $field_builder
      ->setField('text_long', [], [])
      ->setWidget('text_textarea')
      ->setDisplay('text_default', [], 'hidden');
  }

}
