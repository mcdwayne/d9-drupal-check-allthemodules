<?php

namespace Drupal\better_subthemes\Tests;

use Drupal\block\Entity\Block;
use Drupal\simpletest\WebTestBase;

/**
 * Tests block layout inheritance.
 *
 * @group Better sub-themes
 */
class BetterSubthemesBlockLayoutTest extends WebTestBase {

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['better_subthemes_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create administrative user.
    $this->adminUser = $this->drupalCreateUser(['administer blocks']);

    // Login as the administrative user.
    $this->drupalLogin($this->adminUser);

    // Enable the test themes.
    $prefix = 'better_subthemes';
    \Drupal::service('theme_handler')->install([
      "{$prefix}_destination",
      "{$prefix}_source",
    ]);
  }

  /**
   * Checks to see whether a block appears on the page in a specified region.
   *
   * @param \Drupal\block\Entity\Block $block
   *   The block entity to find.
   * @param string $region_name
   *   The region  in which to find the block entity.
   */
  protected function assertBlockAppearsInRegion(Block $block, $region_name) {
    $xpath = $this->buildXPathQuery("//{$region_name}//div[@id=:block-id]/*", [
      ':block-id' => 'block-' . str_replace('_', '-', strtolower($block->id())),
    ]);
    $this->assertFieldByXPath($xpath, NULL, t('Block found in %region_name region.', ['%region_name' => $region_name]));
  }

  /**
   * Test the block layout inheritance.
   */
  public function testBlockLayoutInheritance() {
    $prefix = 'better_subthemes';

    // Set the 'destination' theme as our active theme.
    $this->config('system.theme')
      ->set('default', "{$prefix}_destination")
      ->save();

    // Place a block in the 'destination' theme.
    $label_destination = 'Better sub-themes test - Destination block';
    $block_destination = $this->drupalPlaceBlock('better_subthemes_test_block', [
      'label'  => $label_destination,
      'region' => 'content',
      'theme'  => "{$prefix}_destination",
    ]);
    $this->drupalGet("/admin/structure/block/list/{$prefix}_destination");
    $this->assertText($label_destination);

    // As we should be inheriting the block layout from our 'source' theme, the
    // newly placed block should not be visible.
    $this->drupalGet('<front>');
    $this->assertNoBlockAppears($block_destination);

    // Place a block in the 'source' theme.
    $label_source = 'Better sub-themes test - Source block';
    $block_source = $this->drupalPlaceBlock('better_subthemes_test_block', [
      'label'  => $label_source,
      'region' => 'content',
      'theme'  => "{$prefix}_source",
    ]);
    $this->drupalGet("/admin/structure/block/list/{$prefix}_source");
    $this->assertText($label_source);

    // As we should be inheriting the block layout from our 'source' theme, the
    // newly placed block should be visible.
    $this->drupalGet('<front>');
    $this->assertBlockAppears($block_source);
  }

  /**
   * Test the block layout remapping.
   */
  public function testBlockLayoutRemap() {
    $prefix = 'better_subthemes';

    // Place a block in the 'source' theme.
    $label_source = 'Better sub-themes test - Source block';
    $block_source = $this->drupalPlaceBlock('better_subthemes_test_block', [
      'label'  => $label_source,
      'region' => 'header',
      'theme'  => "{$prefix}_source",
    ]);
    $this->drupalGet("/admin/structure/block");;

    // Set the 'source' theme as our active theme.
    $this->config('system.theme')
      ->set('default', "{$prefix}_source")
      ->save();

    // As the 'source' theme is active, we should see the block in the header
    // region.
    $this->drupalGet('<front>');
    $this->assertBlockAppearsInRegion($block_source, 'header');

    // Set the 'destination' theme as our active theme.
    $this->config('system.theme')
      ->set('default', "{$prefix}_destination")
      ->save();

    // As the 'destination' theme is active, we should see the block in the
    // footer region.
    $this->drupalGet('<front>');
    $this->assertBlockAppearsInRegion($block_source, 'footer');
  }

}
