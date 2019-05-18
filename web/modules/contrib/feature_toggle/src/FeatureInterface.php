<?php

namespace Drupal\feature_toggle;

/**
 * The feature wrapper interface.
 */
interface FeatureInterface {

  /**
   * Returns the feature name.
   *
   * @return string
   *   The feature name.
   */
  public function name();

  /**
   * Returns the feature label.
   *
   * @return string
   *   The feature label.
   */
  public function label();

}
