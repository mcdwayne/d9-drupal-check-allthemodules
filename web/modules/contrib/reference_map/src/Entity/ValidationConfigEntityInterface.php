<?php

namespace Drupal\reference_map\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines a common interface for configuration entities with validation.
 */
interface ValidationConfigEntityInterface extends ConfigEntityInterface {

  /**
   * Validates the currently set values.
   *
   * @return \Symfonay\Component\Validator\ConstraintViolationListInterface
   *   A list of constraint violations. If the list is empty, validation
   *   succeeded.
   */
  public function validate();

  /**
   * Checks whether entity validation is required before saving the entity.
   *
   * @return bool
   *   TRUE if validation is required, FALSE if not.
   */
  public function isValidationRequired();

  /**
   * Sets whether entity validation is required before saving the entity.
   *
   * @param bool $required
   *   TRUE if validation is required, FALSE otherwise.
   *
   * @return $this
   */
  public function setValidationRequired($required);

}
