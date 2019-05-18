<?php

namespace Drupal\integro\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures the validity of the definition.
 *
 * @Constraint(
 *   id = "IntegroDefinition",
 *   label = @Translation("Integro Definition", context = "Validation")
 * )
 */
class DefinitionConstraint extends Constraint {

  /**
   * The invalid definition message.
   *
   * @var string
   */
  public $invalidDefinition = "The integration definition '@definition' is unknown by integration framework.";

}
