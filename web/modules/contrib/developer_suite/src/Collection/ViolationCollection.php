<?php

namespace Drupal\developer_suite\Collection;

use Drupal\developer_suite\Collection;
use Drupal\developer_suite\Validator\BaseValidatorInterface;

/**
 * Class ViolationCollection.
 *
 * @package Drupal\developer_suite\Validator
 */
class ViolationCollection extends Collection implements ViolationCollectionInterface {

  /**
   * Adds a violation.
   *
   * @param \Drupal\developer_suite\Validator\BaseValidatorInterface $violation
   *   The violation.
   */
  public function addViolation(BaseValidatorInterface $violation) {
    $this->add($violation);
  }

  /**
   * Returns the violations.
   *
   * @return \Drupal\developer_suite\Validator\BaseValidatorInterface[]
   *   The violations.
   */
  public function getViolations() {
    return $this->items;
  }

}
