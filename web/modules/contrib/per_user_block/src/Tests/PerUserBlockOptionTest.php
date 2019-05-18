<?php

/**
 * @file
 * Definition of Drupal\per_user_block\Tests\PerUserBlockOptionTest.
 */

namespace Drupal\per_user_block\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\simpletest\WebTestBase;
use Drupal\block\Entity\Block;

/**
 * Tests the block system with admin themes.
 *
 * @group Per User Block
 */
class PerUserBlockOptionTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'per_user_block');

  /**
   * Provide basic information about our tests.
   *
   * @return array
   *   This tests information.
   */
  public static function getInfo() {
    return array(
      'name' => 'Per User Block',
      'description' => 'Tests that the options appear on the block configure page.',
      'group' => 'Per User Block',
    );
  }

  /**
   * Test that the per_user_block options exist.
   */
  public function testOptionsExist() {
    // Create administrative user.
    $admin_user = $this->drupalCreateUser(array(
      'administer blocks',
      'administer themes',
      'administer users',
    ));
    $this->drupalLogin($admin_user);

    // Add a test block, any block will do.
    // Set the machine name so the link can be built later.
    $block_id = Unicode::strtolower($this->randomMachineName(16));
    $this->drupalPlaceBlock('system_powered_by_block', [
      'id' => $block_id,
      'region' => 'content',
      'label_display' => 'visible',
    ]);
    $block = Block::load($block_id);
    $configuration = $block->getPlugin()->getConfiguration();

    // Ensure it appears on the homepage by default.
    $this->drupalGet('');
    $this->assertText($configuration['label'], "Block " . $configuration['label'] . " is appearing on the homepage.");

    // Get the Block manage page and ensure the settings exist.
    $block_path = 'admin/structure/block/manage/' . $block_id;
    $this->drupalGet($block_path);
    $element = $this->xpath('//details[@id=:id]/*', array(':id' => 'edit-third-party-settings-per-user-block'));
    $this->assertTrue(!empty($element), 'Per user block visibility settings exist.');

    // Change the value to disabled and validate it was saved.
    $edit = array();
    $edit['third_party_settings[per_user_block][visibility]'] = PER_USER_BLOCK_CUSTOM_DISABLED;
    $this->drupalPostForm($block_path, $edit, 'Save block');

    $block = Block::load($block->id());
    $visibility = $block->getThirdPartySetting('per_user_block', 'visibility', PER_USER_BLOCK_CUSTOM_FIXED);
    $this->assertTrue($visibility == PER_USER_BLOCK_CUSTOM_DISABLED, 'New value was customizable, hidden by default.');

    // Ensure the settings appear on the users page.
    $configuration = $block->getPlugin()->getConfiguration();
    $this->drupalGet('user/2/edit');
    $this->assertText($configuration['label'], "Block " . $configuration['label'] . " is available on the settings page.");

    // Ensure that it's also currently set to disabled as we previously set.
    $this->assertNoFieldChecked("edit-blocks-$block_id", "Block " . $configuration['label'] . " was set to hidden.");
    // Navigate to the homepage and ensure the block doesn't exist.
    $this->drupalGet('');
    $this->assertNoText($configuration['label'], "Block " . $configuration['label'] . " is not appearing on the homepage.");

    // Change the value to enabled..
    $edit = array();
    $edit['blocks[' . $block_id . ']'] = 1;
    $this->drupalPostForm('user/2/edit', $edit, 'Save');
    $this->assertFieldChecked("edit-blocks-$block_id", "Block " . $configuration['label'] . " was set to enabled.");

    // Navigate to the homepage and make sure our block exists.
    $this->drupalGet('');
    $this->assertText($configuration['label'], "Block " . $configuration['label'] . " is appearing on the homepage.");
  }

}
