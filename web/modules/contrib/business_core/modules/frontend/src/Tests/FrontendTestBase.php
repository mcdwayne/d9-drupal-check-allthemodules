<?php

namespace Drupal\frontend\Tests;

use Drupal\simpletest\WebTestBase;

abstract class FrontendTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['frontend'];

}
