<?php

namespace Drupal\media_entity_dreambroker\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Check if a value is a valid Dream Broker embed code/URL.
 *
 * @constraint(
 *   id = "DreambrokerEmbedCode",
 *   label = @Translation("Dreambroker embed code", context = "Validation"),
 *   type = { "link", "string", "string_long" }
 * )
 */
class DreambrokerEmbedCodeConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Not valid Dream Broker URL/Embed code.';

}
