<?php

namespace Drupal\icon_field\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the user-facing menus in Icon Field.
 *
 * @group icon_field
 * @group icon
 *
 * @ingroup icon_field
 */
class IconFieldMenuTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('icon_field');

  /**
   * The installation profile to use with this test.
   *
   * This test class requires the "Tools" block.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * Test for a link to the block example in the Tools menu.
   */
  public function testFieldExampleLink() {
    $this->drupalGet('');
    $this->assertLinkByHref('examples/field-example');
  }

  /**
   * Tests field_example menus.
   */
  public function testBlockExampleMenu() {
    $this->drupalGet('examples/field-example');
    $this->assertResponse(200, 'Description page exists.');
  }

}
