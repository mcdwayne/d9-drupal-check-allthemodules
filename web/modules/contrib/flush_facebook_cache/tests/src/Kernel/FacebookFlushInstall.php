<?php

namespace Drupal\Tests\facebook_flush_cache\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Install the module.
 *
 * @group facebook_flush_cache_kernel
 */
class FacebookFlushInstall extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['facebook_flush_cache'];

  /**
   * Tests that an Email Log entity is created on Sendgrid event.
   */
  public function testFoo() {

    $module = \Drupal::moduleHandler()->moduleExists('facebook_flush_cache');

    $this->assertTrue($module);
  }

}
