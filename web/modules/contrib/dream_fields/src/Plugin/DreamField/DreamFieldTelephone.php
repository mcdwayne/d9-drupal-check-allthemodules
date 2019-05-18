<?php

namespace Drupal\dream_fields\Plugin\DreamField;

use Drupal\dream_fields\DreamFieldPluginBase;
use Drupal\dream_fields\FieldBuilderInterface;

/**
 * Plugin implementation of 'telephone'.
 *
 * @DreamField(
 *   id = "telephone",
 *   label = @Translation("Telephone"),
 *   description = @Translation("This will add an input field for an phone number and will be outputted with a link."),
 *   weight = -8,
 *   preview = "images/textfield-dreamfields.png",
 *   preview_provider = "dream_fields",
 *   provider = "telephone",
 *   field_types = {
 *     "telephone"
 *   },
 * )
 */
class DreamFieldTelephone extends DreamFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function saveForm($values, FieldBuilderInterface $field_builder) {
    $field_builder
      ->setWidget('telephone_default')
      ->setField('telephone')
      ->setDisplay('telephone_link', [], 'inline');
  }

}
