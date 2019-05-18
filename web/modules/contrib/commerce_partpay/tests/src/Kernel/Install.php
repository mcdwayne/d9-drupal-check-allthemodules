<?php

namespace Drupal\Tests\commerce_partpay\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Install the module.
 *
 * @group commerce_partpay
 */
class Install extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['commerce_partpay'];

  /**
   * Tests that an Email Log entity is created on Sendgrid event.
   */
  public function testModulesCanBeInstalled() {

    $module = \Drupal::moduleHandler()->moduleExists('commerce_partpay');

    $this->assertTrue($module);
  }

}
