<?php

namespace Drupal\media_entity_smugmug\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Check if a value is a valid SmugMug embed code or post URL.
 *
 * @constraint(
 *   id = "SmugMugEmbedCode",
 *   label = @Translation("SmugMug embed code", context = "Validation"),
 *   type = { "link", "string", "string_long" }
 * )
 */
class SmugMugEmbedCodeConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Not valid SmugMug post URL/embed code.';

}
