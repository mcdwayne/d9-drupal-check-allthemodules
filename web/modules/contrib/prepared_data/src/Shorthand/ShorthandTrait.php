<?php

namespace Drupal\prepared_data\Shorthand;

/**
 * Trait for working with shorthands.
 */
trait ShorthandTrait {

  /**
   * The factory for shorthands.
   *
   * @var \Drupal\prepared_data\Shorthand\ShorthandsFactory
   */
  protected $shorthands;

  /**
   * Get the shorthands factory.
   *
   * @return \Drupal\prepared_data\Shorthand\ShorthandsFactory
   *   The factory for shorthands.
   */
  public function shorthands() {
    if (!isset($this->shorthands)) {
      $this->setShorthands(\Drupal::service('prepared_data.shorthands'));
    }
    return $this->shorthands;
  }

  /**
   * Set the shorthands factory.
   *
   * @param \Drupal\prepared_data\Shorthand\ShorthandsFactory $factory
   *   The factory for shorthands.
   */
  public function setShorthands(ShorthandsFactory $factory) {
    $this->shorthands = $factory;
  }

}
