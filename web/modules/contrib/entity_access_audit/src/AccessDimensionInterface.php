<?php

namespace Drupal\entity_access_audit;

/**
 * Interface AccessDimensionInterface
 */
interface AccessDimensionInterface {

  /**
   * The label of the dimension.
   *
   * @return string
   *   The human readable name of the dimension.
   */
  public static function getLabel();

  /**
   * The value of the given dimension used for access checking.
   *
   * @return string
   *   The human readable value of this dimension.
   */
  public function getDimensionValue();

  /**
   * Get an identifier for the dimension.
   */
  public function id();

}
