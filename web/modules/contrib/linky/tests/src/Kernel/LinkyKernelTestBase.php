<?php

namespace Drupal\Tests\linky\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test base for linky kernel tests.
 */
abstract class LinkyKernelTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'linky',
    'link',
    'dynamic_entity_reference',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('linky');
  }

}

// Global constants hack.
if (!defined('DRUPAL_OPTIONAL')) {
  define('DRUPAL_DISABLED', 0);
  define('DRUPAL_OPTIONAL', 1);
  define('DRUPAL_REQUIRED', 2);
}
