<?php

namespace Drupal\entity_resource_layer\Exception;

use Symfony\Component\Validator\Constraint;

/**
 * Class EntityResourceConstraintException.
 *
 * @package Drupal\entity_resource_layer\Exception
 */
class EntityResourceConstraintException extends EntityResourceException {

  /**
   * Handled constraints.
   */
  const CONSTRAINT_ERROR = [
    'Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraint'      => 'FIELD_INV_REF',
    'Drupal\Core\Entity\Plugin\Validation\Constraint\ReferenceAccessConstraint'     => 'FIELD_ACCESS_DENIED_REF',

    'Drupal\Core\Validation\Plugin\Validation\Constraint\AllowedValuesConstraint'   => [
      'type' => 'FIELD_INV_VALUES',
      'attach' => ['choices', 'multiple', 'strict', 'min', 'max']
    ],
    'Drupal\Core\Validation\Plugin\Validation\Constraint\CountConstraint'           => [
      'type' => 'FIELD_INV_AMOUNT',
      'attach' => ['min', 'max'],
    ],
    'Drupal\Core\Validation\Plugin\Validation\Constraint\LengthConstraint'          => [
      'type' => 'FIELD_INV_LENGTH',
      'attach' => ['min', 'max'],
    ],
    'Drupal\Core\Validation\Plugin\Validation\Constraint\RangeConstraint'           => [
      'type' => 'FIELD_OUT_OF_RANGE',
      'attach' => ['min', 'max'],
    ],
    'Drupal\Core\Validation\Plugin\Validation\Constraint\RegexConstraint'           => [
      'type' => 'FIELD_INV_FORMAT',
      'attach' => ['pattern']
    ],

    'Drupal\Core\Validation\Plugin\Validation\Constraint\ComplexDataConstraint'     => 'FIELD_COMPLEX',
    'Drupal\Core\Validation\Plugin\Validation\Constraint\EmailConstraint'           => 'FIELD_INV_EMAIL',
    'Drupal\Core\Validation\Plugin\Validation\Constraint\NotNullConstraint'         => 'FIELD_REQUIRED',
    'Drupal\Core\Validation\Plugin\Validation\Constraint\IsNullConstraint'          => 'FIELD_NEEDS_NULL',
    'Drupal\Core\Validation\Plugin\Validation\Constraint\PrimitiveTypeConstraint'   => 'FIELD_INV_PRIMITIVE_TYPE',
    'Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldConstraint'     => 'FIELD_NOT_UNIQUE',
  ];

  /**
   * The error type.
   *
   * @var string
   */
  protected $errorType;

  /**
   * Constraint information.
   *
   * @var array
   */
  protected $constraints = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct($message = NULL, $errorType = NULL) {
    $errorType = $errorType ?: 'UNKNOWN';
    $this->errorType = $errorType;
    parent::__construct($message);
  }

  /**
   * Adds information from the constraint to the exception.
   *
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   The constraint that failed.
   *
   * @return \Drupal\entity_resource_layer\Exception\EntityResourceConstraintException
   *   This.
   */
  public function addConstraintInformation(Constraint $constraint) {
    foreach (static::CONSTRAINT_ERROR as $class => $data) {
      if ($constraint instanceof $class) {
        $info = $data;
        break;
      }
    }

    if (!isset($info)) {
      // Allow custom constraints to declare their type.
      if (defined(get_class($constraint) . '::CONSTRAINT_ID')) {
        $this->errorType = get_class($constraint)::CONSTRAINT_ID;
      }
      return $this;
    }

    if (is_string($info)) {
      $this->errorType = $info;
      return $this;
    }

    $this->errorType = $info['type'];

    if (array_key_exists('attach', $info)) {
      foreach ($info['attach'] as $attach) {
        $this->constraints[$attach] = $constraint->{$attach};
      }
    }

    return $this;
  }

  /**
   * Adds custom constraint information.
   *
   * @param array $constraints
   *   Constraint data.
   *
   * @return $this
   */
  public function addCustomConstraints(array $constraints) {
    $this->constraints = $constraints;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceData($includeCode = FALSE) {
    $data = ['error' => $this->errorType];

    if ($this->constraints) {
      $data['constraints'] = $this->constraints;
    }

    return parent::getResourceData($includeCode) + $data;
  }

}
