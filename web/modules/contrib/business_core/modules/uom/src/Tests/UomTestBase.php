<?php

namespace Drupal\uom\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides helper functions.
 */
abstract class UomTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['uom'];

}
