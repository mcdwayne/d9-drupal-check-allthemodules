<?php

namespace Drupal\xero\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Provides the GUID validation from xero module drupal 7.
 *
 * @Plugin(
 *   id = "XeroGuidConstraint",
 *   label = @Translation("Guid Constraint", context = "Validation")
 * )
 */
class XeroGuidConstraint extends Constraint {
  public $message = 'This value should be a globally-unique identifier.';
}
