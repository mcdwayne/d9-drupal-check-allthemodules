<?php

namespace Drupal\singleton_test;

use Drupal\singleton\Singleton\SingletonInterface;
use Drupal\singleton\Singleton\SingletonTrait;

/**
 * Test class to make sure classes provided are loaded in by Drupal services.
 */
class SingletonTest implements SingletonInterface {

  use SingletonTrait;

  /**
   * Test function used to check to make sure service works.
   *
   * @return string
   *   Returns "bar".
   */
  public function foo() {
    return t('bar');
  }

}
