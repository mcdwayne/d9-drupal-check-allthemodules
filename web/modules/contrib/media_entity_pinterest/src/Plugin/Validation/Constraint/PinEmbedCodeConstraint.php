<?php

namespace Drupal\media_entity_pinterest\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a value is a valid Pin embed code/URL.
 *
 * @Constraint(
 *   id = "PinEmbedCode",
 *   label = @Translation("Pin embed code", context = "Validation"),
 *   type = { "link", "string", "string_long" }
 * )
 */
class PinEmbedCodeConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Not valid Pin URL/embed code.';

}
