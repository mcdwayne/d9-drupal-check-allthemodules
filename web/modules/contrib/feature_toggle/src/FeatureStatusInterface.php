<?php

namespace Drupal\feature_toggle;

/**
 * Interface FeatureStatusInterface.
 */
interface FeatureStatusInterface {

  /**
   * Returns the feature status.
   *
   * @param string $name
   *   The feature name.
   *
   * @return bool
   *   The feature status.
   */
  public function getStatus($name);

  /**
   * Sets the feature status value.
   *
   * @param FeatureInterface $feature
   *   The feature to update.
   * @param bool $status
   *   The feature status value.
   */
  public function setStatus(FeatureInterface $feature, $status);

}
