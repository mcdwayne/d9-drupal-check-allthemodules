<?php

namespace Drupal\command_bus\Validator;

use Countable;

/**
 * Class Violations.
 *
 * @package Drupal\command_bus\Validator
 */
class Violations implements Countable {

  /**
   * The violations.
   *
   * @var array|\Drupal\command_bus\Validator\ValidatorInterface[]
   */
  private $violations = [];

  /**
   * Add violations.
   *
   * @param \Drupal\command_bus\Validator\ValidatorInterface $violation
   *   The violation.
   */
  public function addViolation(ValidatorInterface $violation) {
    $this->violations[] = $violation;
  }

  /**
   * Returns the violations.
   *
   * @return \Drupal\command_bus\Validator\ValidatorInterface[]
   *   The violations.
   */
  public function getViolations() {
    return $this->violations;
  }

  /**
   * Returns the violation count.
   *
   * @return int
   *   The violation count.
   */
  public function count() {
    return count($this->violations);
  }

}
