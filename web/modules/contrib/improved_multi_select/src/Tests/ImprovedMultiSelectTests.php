<?php

namespace Drupal\improved_multi_select\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Class ImprovedMultiSelectTests.
 *
 * Improved Multi Select Tests.
 *
 * @group improved_multi_select
 *
 * @package Drupal\improved_multi_select\Tests
 */
class ImprovedMultiSelectTests extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['improved_multi_select'];

  /**
   * Test improved_multi_select_load_selectors() function.
   */
  public function testImsLoadSelectors() {
    $replace_all = FALSE;
    $selectors = [];
    $jquery_selectors = improved_multi_select_load_selectors($replace_all, $selectors);
    $this->assertIdentical($jquery_selectors, ['select[multiple]']);
    $replace_all = TRUE;
    $jquery_selectors = improved_multi_select_load_selectors($replace_all, $selectors);
    $this->assertIdentical($jquery_selectors, ['select[multiple]']);
    $selectors = ['test_selector'];
    $jquery_selectors = improved_multi_select_load_selectors($replace_all, $selectors);
    $this->assertIdentical($jquery_selectors, ['select[multiple]']);
    $replace_all = FALSE;
    $jquery_selectors = improved_multi_select_load_selectors($replace_all, $selectors);
    $this->assertIdentical($jquery_selectors, ['test_selector']);
  }

}
