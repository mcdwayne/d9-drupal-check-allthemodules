<?php

namespace Drupal\developer_suite\Collection;

use Drupal\developer_suite\CollectionInterface;
use Drupal\developer_suite\Validator\BaseValidatorInterface;

/**
 * Interface ViolationCollectionInterface.
 *
 * @package Drupal\developer_suite\Collection
 */
interface ViolationCollectionInterface extends CollectionInterface {

  /**
   * Adds a violation.
   *
   * @param \Drupal\developer_suite\Validator\BaseValidatorInterface $violation
   *   The violation.
   */
  public function addViolation(BaseValidatorInterface $violation);

  /**
   * Returns the iterator.
   *
   * @return \ArrayIterator
   *   The array iterator.
   */
  public function getIterator();

  /**
   * Returns the violations.
   *
   * @return \Drupal\developer_suite\Validator\FormValidatorInterface[]
   *   The violations.
   */
  public function getViolations();

}
