<?php

namespace Drupal\reactjs_page\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test reactjs_page module.
 *
 * @group reactjs_page
 */
class ReactjsPageTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['reactjs_page', 'reactjs_page_test'];

  /**
   * Tests the reactjs_page module.
   */
  public function testModule() {
    $this->drupalGet('test');
    $this->assertResponse(200);
  }

}
