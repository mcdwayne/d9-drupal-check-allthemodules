<?php

namespace Drupal\reference_map\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Defines a base configuration entity class with validation.
 */
class ValidationConfigEntityBase extends ConfigEntityBase implements ValidationConfigEntityInterface {

  /**
   * Whether entity validation was performed.
   *
   * @var bool
   */
  protected $validated = FALSE;

  /**
   * Whether entity validation is required before saving the entity.
   *
   * @var bool
   */
  protected $validationRequired = FALSE;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // An entity requiring validation should not be saved if it has not been
    // actually validated.
    if ($this->validationRequired && !$this->validated) {

      // @todo Make this an assertion in https://www.drupal.org/node/2408013.
      throw new \LogicException('Entity validation was skipped.');
    }
    else {
      $this->validated = FALSE;
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $this->validated = TRUE;
    $violations = $this
      ->getTypedData()
      ->validate();
    return new ConstraintViolationList(iterator_to_array($violations));
  }

  /**
   * {@inheritdoc}
   */
  public function isValidationRequired() {
    return (bool) $this->validationRequired;
  }

  /**
   * {@inheritdoc}
   */
  public function setValidationRequired($required) {
    $this->validationRequired = $required;
    return $this;
  }

}
