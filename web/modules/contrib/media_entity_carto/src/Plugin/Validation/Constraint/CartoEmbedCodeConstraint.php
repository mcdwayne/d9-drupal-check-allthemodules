<?php

namespace Drupal\media_entity_carto\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a value is a valid CARTO embed code/URL.
 *
 * @Constraint(
 *   id = "CartoEmbedCode",
 *   label = @Translation("CARTO embed code", context = "Validation"),
 *   type = { "link", "string", "string_long" }
 * )
 */
class CartoEmbedCodeConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Not valid CARTO Map URL/embed code.';

}
