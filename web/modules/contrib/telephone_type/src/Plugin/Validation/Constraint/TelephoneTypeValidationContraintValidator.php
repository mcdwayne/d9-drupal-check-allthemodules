<?php

namespace Drupal\telephone_type\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Validates the LinkExternalProtocols constraint.
 */
class TelephoneTypeValidationContraintValidator implements ConstraintValidatorInterface {

  /**
   * Stores the validator's state during validation.
   *
   * @var \Symfony\Component\Validator\ExecutionContextInterface
   */
  protected $context;

  /**
   * Validator service.
   *
   * @var \Drupal\telephone_type_validation\Validator
   */
  protected $validator;

  /**
   * {@inheritdoc}
   */
  public function initialize(ExecutionContextInterface $context) {
    $this->context = $context;
    $this->validator = \Drupal::service('telephone_type.validator');
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    try {
      $number = $value->getValue();
    }
    catch (\InvalidArgumentException $e) {
      return;
    }

    // Validate number against validation settings.
    if (!$this->validator->isValid($number['value'])) {
      $this->context->addViolation($constraint->message, ['@number' => $number['value']]);
    }
  }

}
