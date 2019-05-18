<?php

namespace Drupal\entity_access_audit;

use Drupal\Core\Access\AccessResultInterface;

/**
 * Value object for the result of an access check.
 */
class AccessAuditResult {

  /**
   * @var \Drupal\Core\Access\AccessResultInterface
   */
  protected $result;

  /**
   * @var \Drupal\entity_access_audit\AccessDimensionInterface[]
   */
  protected $accessDimensions;

  /**
   * Create an instance of AccessAuditResult.
   */
  public function __construct(AccessResultInterface $result, array $accessDimensions) {
    $this->accessDimensions = $accessDimensions;
    $this->result = $result;
  }

  /**
   * Get the access result from the access system.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function getAccessResult() {
    return $this->result;
  }

  /**
   * Check if an instance of a given dimension exists.
   *
   * @return bool
   *   If the given dimension exists in the audit result.
   */
  public function hasDimension($dimension) {
    return isset($this->accessDimensions[$dimension]);
  }

  /**
   * Get the dimensions of this access audit result.
   *
   * @return \Drupal\entity_access_audit\AccessDimensionInterface[]
   *   Access dimensions.
   */
  public function getDimensions() {
    return $this->accessDimensions;
  }

}
