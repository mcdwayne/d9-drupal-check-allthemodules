<?php

namespace Drupal\feature_toggle;

/**
 * Feature wrapper class.
 */
class Feature implements FeatureInterface {

  /**
   * The feature name.
   *
   * @var string
   */
  protected $name;

  /**
   * The feature label.
   *
   * @var string
   */
  protected $label;

  /**
   * Feature constructor.
   *
   * @param string $name
   *   The feature name.
   * @param string $label
   *   The feature label.
   */
  public function __construct($name, $label) {
    $this->name = $name;
    $this->label = $label;
  }

  /**
   * {@inheritdoc}
   */
  public function name() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

}
