<?php

namespace Drupal\cpf\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Supports validating CPF numbers.
 *
 * @Constraint(
 *   id = "CpfUnique",
 *   label = @Translation("CPF Value", context = "Validation")
 * )
 */
class CpfUniqueConstraint extends Constraint {

  public $entity = NULL;

  public $fieldDefinition = '';

  public $ignoreBundle = 0;

  public $message = 'The CPF number %value already exists. Enter a unique number.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\cpf\Plugin\Validation\Constraint\CpfUniqueConstraintValidator';
  }

}
