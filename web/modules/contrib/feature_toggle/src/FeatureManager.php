<?php

namespace Drupal\feature_toggle;

/**
 * Class FeatureManager.
 */
class FeatureManager implements FeatureManagerInterface {

  use FeatureUtilsTrait;

  /**
   * {@inheritdoc}
   */
  public function featureExists($name) {
    $features = $this->loadFeatures();
    return isset($features[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFeature($name) {
    $features = $this->loadFeatures();
    return isset($features[$name]) ? new Feature($name, $features[$name]) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getFeatures() {
    $features = $this->loadFeatures();
    $result = [];
    foreach ($features as $name => $label) {
      $result[] = new Feature($name, $label);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function addFeature(FeatureInterface $feature) {
    $features = $this->loadFeatures();
    $features[$feature->name()] = $feature->label();
    $this->saveFeatures($features);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFeature($name) {
    $this->deleteConfigFeature($name);
    $this->deleteStatusFlag($name);
  }

  /**
   * Deletes the feature form the config object.
   *
   * @param string $name
   *   The feature name.
   */
  protected function deleteConfigFeature($name) {
    $features = $this->loadFeatures();
    unset($features[$name]);
    $this->saveFeatures($features);
  }

  /**
   * Delete the feature from the state array.
   *
   * @param string $name
   *   The feature name.
   */
  protected function deleteStatusFlag($name) {
    $flags = $this->getStatusFlags();
    unset($flags[$name]);
    $this->saveStatusFlags($flags);
  }

}
