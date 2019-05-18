<?php

namespace Drupal\feature_toggle\Event;

use Drupal\feature_toggle\FeatureInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Feature Update Event class.
 */
class FeatureUpdateEvent extends Event {

  /**
   * The updated feature.
   *
   * @var \Drupal\feature_toggle\FeatureInterface
   */
  protected $feature;

  /**
   * The new feature status.
   *
   * @var bool
   */
  protected $status;

  /**
   * FeatureUpdate constructor.
   *
   * @param \Drupal\feature_toggle\FeatureInterface $feature
   *   The updated feature.
   * @param bool $status
   *   The new feature status.
   */
  public function __construct(FeatureInterface $feature, $status) {
    $this->feature = $feature;
    $this->status = $status;
  }

  /**
   * Returns the feature.
   *
   * @return \Drupal\feature_toggle\FeatureInterface
   *   The feature triggering the event.
   */
  public function feature() {
    return $this->feature;
  }

  /**
   * Returns the status.
   *
   * @return bool
   *   The feature status.
   */
  public function status() {
    return $this->status;
  }

}
