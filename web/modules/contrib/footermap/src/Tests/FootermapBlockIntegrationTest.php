<?php
/**
 * @file
 * Contains \Drupal\footermap\Tests\FootermapBlockIntegrationTest.
 */

namespace Drupal\footermap\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests footermap block as part of a Drupal installation.
 *
 * @group footermap
 */
class FootermapBlockIntegrationTest extends WebTestBase {

  /**
   * @var array
   */
  public static $modules = ['block', 'path', 'menu_ui', 'footermap'];

  /**
   * @var \Drupal\footermap\Plugin\Block\FootermapBlock
   */
  protected $block;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $settings = [
      'label' => 'Footermap',
      'footermap_recurse_limit' => '0',
      'footermap_display_heading' => '1',
      'footermap_avail_menus' => [],
      'footermap_top_menu' => ''
    ];

    $this->block = $this->drupalPlaceBlock('footermap_block', $settings);
  }

  /**
   * Assert that the footermap block appears on the front page.
   */
  function testFrontPage() {
    $this->drupalGet('');
    $this->assertBlockAppears($this->block);
    $this->assertText('Footermap', 'Found "Footermap" text on front page.');
    $this->assertResponse(200);
  }
}