<?php

namespace Drupal\singleton\Singleton;

/**
 * Represents the required functions for a Singleton.
 */
interface SingletonInterface {

  /**
   * Returns instance, if instance does not exist then creates new one
   * and returns it.
   *
   * @return $this
   */
  public static function getInstance();

}
