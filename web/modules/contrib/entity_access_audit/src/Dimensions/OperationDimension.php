<?php

namespace Drupal\entity_access_audit\Dimensions;

use Drupal\entity_access_audit\AccessDimensionInterface;

/**
 * Dimension for user roles.
 */
class OperationDimension implements AccessDimensionInterface {

  /**
   * The operation being tested.
   *
   * @var string
   */
  protected $operation;

  /**
   * OperationDimension constructor.
   */
  public function __construct($operation) {
    $this->operation = $operation;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Operation');
  }

  /**
   * {@inheritdoc}
   */
  public function getDimensionValue() {
    return $this->operation;
  }

  /**
   * Get the operation.
   *
   * @return string
   *   The operation.
   */
  public function getOperation() {
    return $this->operation;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->operation;
  }

}
