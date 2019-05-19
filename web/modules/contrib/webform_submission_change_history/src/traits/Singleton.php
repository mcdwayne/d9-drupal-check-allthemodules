<?php

namespace Drupal\webform_submission_change_history\traits;

/**
 * Implements the Singleton design pattern.
 */
trait Singleton {

  /**
   * Interal instance variable used with the instance() method.
   *
   * @var object
   */
  static private $instance;

  /**
   * Implements the Singleton design pattern.
   *
   * Only one instance of the Concord class should exist per execution.
   *
   * @return Concord
   *   The single instance of the concord class.
   */
  static public function instance() {
    // See http://stackoverflow.com/questions/15443458
    $class = get_called_class();

    // Not sure why the linter tells me $instance is not used.
    // @codingStandardsIgnoreStart
    if (!$class::$instance) {
    // @codingStandardsIgnoreEnd
      $class::$instance = new $class();
    }
    return $class::$instance;
  }

}
