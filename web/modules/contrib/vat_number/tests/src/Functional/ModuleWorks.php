<?php

namespace Drupal\Tests\vat_number\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Mostly just tests that we can enable the module.
 *
 * @group vat_number
 */
class ModuleWorks extends BrowserTestBase {

  public static $modules = ['vat_number'];

  /**
   * Tests that module is enabled.
   */
  public function testThatModuleIsEnabled() {
    static::assertTrue($this->container->get('module_handler')->moduleExists('vat_number'), 'VAT Number module is running.');
  }

}
