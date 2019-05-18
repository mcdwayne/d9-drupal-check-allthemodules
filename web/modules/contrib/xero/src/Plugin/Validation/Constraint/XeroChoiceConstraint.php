<?php

namespace Drupal\xero\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraints\Choice;

/**
 * @Plugin(
 *   id = "XeroChoiceConstraint",
 *   label = @Translation("Xero Choice Constraint", context = "Validation")
 * )
 */
class XeroChoiceConstraint extends Choice { }