<?php

namespace Drupal\feature_toggle;

/**
 * Interface FeatureManagerInterface.
 */
interface FeatureManagerInterface {

  /**
   * Check whether a feature exists or not.
   *
   * @param string $name
   *   The feature name.
   *
   * @return bool
   *   TRUE if the feature exists. FALSE otherwise.
   */
  public function featureExists($name);

  /**
   * Returns the feature object given the feature name.
   *
   * @param string $name
   *   The feature name.
   *
   * @return \Drupal\feature_toggle\FeatureInterface
   *   The feature object.
   */
  public function getFeature($name);

  /**
   * Returns the list of features.
   *
   * @return \Drupal\feature_toggle\FeatureInterface[]
   *   The feature object array.
   */
  public function getFeatures();

  /**
   * Adds a new feature to the system.
   *
   * @param \Drupal\feature_toggle\FeatureInterface $feature
   *   The feature object.
   */
  public function addFeature(FeatureInterface $feature);

  /**
   * Deletes a feature from the system.
   *
   * @param string $name
   *   The feature name.
   */
  public function deleteFeature($name);

}
