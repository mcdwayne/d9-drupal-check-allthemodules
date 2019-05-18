<?php

namespace Drupal\Tests\config_token\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Config Token tests.
 *
 * @group config_token
 *
 * Class ConfigTokenTest
 * @package Drupal\Tests\config_token\Functional
 */
class ConfigTokenTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_token', 'node');


  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

  }
  /**
   * Basic test setup.
   */
  public function testExample() {

  }

}
