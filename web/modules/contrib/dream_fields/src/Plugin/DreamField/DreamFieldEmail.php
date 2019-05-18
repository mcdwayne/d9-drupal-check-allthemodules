<?php

namespace Drupal\dream_fields\Plugin\DreamField;

use Drupal\dream_fields\DreamFieldPluginBase;
use Drupal\dream_fields\FieldBuilderInterface;

/**
 * Plugin implementation of 'email'.
 *
 * @DreamField(
 *   id = "email",
 *   label = @Translation("Email address"),
 *   description = @Translation("This will add an input field for an email address and will be outputted with a link."),
 *   weight = -5,
 *   preview = "images/email-dreamfields.png",
 *   field_types = {
 *     "email"
 *   },
 * )
 */
class DreamFieldEmail extends DreamFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function saveForm($values, FieldBuilderInterface $field_builder) {
    $field_builder
      ->setField('email')
      ->setDisplay('email_mailto')
      ->setWidget('email_default');
  }

}
