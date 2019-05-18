<?php

namespace Drupal\Tests\commerce_xero\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests installing the Drupal Commerce Xero module on a fresh Drupal site.
 *
 * @group commerce_xero
 */
class InstallTest extends WebDriverTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_order',
    'commerce_price',
    'commerce_xero',
    'serialization',
    'options',
    'views',
    'xero',
  ];

  /**
   * Tests installing the Drupal Commerce Xero module on a fresh Drupal site.
   */
  public function testInstall() {
    $module_list = $this->container->get('module_handler')->getModuleList();
    $this->assertArrayHasKey('commerce_xero', $module_list);
  }

}
