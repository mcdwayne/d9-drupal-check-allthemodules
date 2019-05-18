<?php

namespace Drupal\media_entity_issuu\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Check if a value is a valid Issuu embed code or post URL.
 *
 * @constraint(
 *   id = "IssuuEmbedCode",
 *   label = @Translation("Issuu embed code", context = "Validation"),
 *   type = { "link", "string", "string_long" }
 * )
 */
class IssuuEmbedCodeConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Not valid Issuu post URL/embed code.';

}
