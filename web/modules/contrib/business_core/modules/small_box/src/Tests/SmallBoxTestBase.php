<?php

namespace Drupal\small_box\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides helper functions.
 */
abstract class SmallBoxTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['small_box'];

}
