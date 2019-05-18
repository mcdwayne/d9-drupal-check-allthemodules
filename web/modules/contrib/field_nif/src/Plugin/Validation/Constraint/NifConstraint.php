<?php

namespace Drupal\field_nif\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if field_nif value is valid.
 *
 * @Constraint(
 *   id = "NifValue",
 *   label = @Translation("NIF/CIF/NIE number constraint", context = "Validation"),
 * )
 */
class NifConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = '@value is not a valid @document_type document number.';

  /**
   * The supported types.
   *
   * @var array
   */
  public $supportedTypes;

  /**
   * Gets the violation message.
   *
   * @return string
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * Gets the supported types as array.
   *
   * @return array
   */
  public function getSupportedTypes() {

    return $this->supportedTypes;
  }

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\field_nif\Plugin\Validation\Constraint\NifValueValidator';
  }

}
