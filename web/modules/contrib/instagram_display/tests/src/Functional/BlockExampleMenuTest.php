<?php

namespace Drupal\Tests\instagram_display\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the user-facing menus in Block Example.
 *
 * @ingroup instagram_display
 *
 * @group instagram_display
 * @group examples
 */
class BlockExampleMenuTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'instagram_display');

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
  public function testBlockExampleLink() {
    $this->drupalGet('');
    $this->assertLinkByHref('examples/block-example');
  }

  /**
   * Tests instagram_display menus.
   */
  public function testBlockExampleMenu() {
    $this->drupalGet('examples/block-example');
    $this->assertResponse(200, 'Description page exists.');
  }

}
