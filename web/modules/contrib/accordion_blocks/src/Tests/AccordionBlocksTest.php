<?php

/**
 * @file
 * Contains Drupal\accordion_blocks\Tests\AccordionBlocksTest.
 * 
 * Test cases for testing accordion_blocks module.
 */

namespace Drupal\accordion_blocks\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test that Accordion Blocks Custom Block Type is created 
 * and block widgets are working.
 *
 * @ingroup accordion_blocks
 * @group accordion_blocks
 */
class AccordionBlocksTest extends WebTestBase{

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('block', 'field', 'field_ui', 'block_content', 'accordion_blocks');
  
  /**
   * The installation profile to use with this test.
   *
   * We need the 'minimal' profile in order to make sure the Tool block is
   * available.
   *
   * @var string
   */
  protected $profile = 'standard';
  
  /**
   * Test our accordion blocks block content type.
   *
   * Tests for the following:
   *
   * - That block content type appear in the user interface.
   * - That block content type has correct blocks entity reference field.
   * - That block content type has correct display format settings.
   */
  public function testBlockContentType() {
    // Log in an admin user.
    $admin_user = $this->drupalCreateUser(array(
      'administer blocks', 
      'administer block_content fields',
      'administer block_content display',
      'administer block_content form display'));
    $this->drupalLogin($admin_user);
    
     // Get a list of Block content types.
    $this->drupalGet('/admin/structure/block/block-content/types');
    // Verify that these content types show up in the user interface.
    $this->assertRaw('Accordion block', 'Accordion Block Content Type found.');
    
    // Get a list of Accordion Blocks conten type fields
    $this->drupalGet('/admin/structure/block/block-content/manage/accordion_block/fields');
    $this->assertRaw('field_blocks', 'Field Blocks is found');
    
    // Check that the blocks field form display is auto complete.
    $this->drupalGet('/admin/structure/block/block-content/manage/accordion_block/form-display');
    $this->assertFieldByXPath('//select[@name="fields[field_blocks][type]"]', 'entity_reference_autocomplete', 'Form Display is auto complete');
    
    // Check display format of the blocks field to be Accordion Widget
    $this->drupalGet('/admin/structure/block/block-content/manage/accordion_block/display');
    $this->assertFieldByXPath('//select[@name="fields[field_blocks][type]"]', 'accordion_widget_formatter', 'Display Accordion Widget');
  }
  
  /**
   * Test create accordion block
   * 
   * Tests for the following:
   * - Create accordion block
   * - Displaying of accordion block on the specified region.
   */
  public function testCreateAccordionBlock() {
    
    $admin_user = $this->drupalCreateUser(array(
      'administer blocks', 
      'administer block_content display'));
    $this->drupalLogin($admin_user);
    
    // Create a block.
    $edit = array();
    $edit['info[0][value]'] = 'Test Block';
    $edit['field_blocks[0][target_id]'] = 'User account menu (bartik_account_menu)';
    $edit['field_blocks[1][target_id]'] = 'Footer menu (bartik_footer)';
    $edit['field_blocks[2][target_id]'] = 'Powered by Drupal (bartik_powered)';
    $this->drupalGet('block/add/accordion_block');
    $this->drupalPostForm(NULL, array(), t('Add another item'));
    $this->drupalPostForm(NULL, array(), t('Add another item'));
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Check that the Basic block has been created.
    $this->assertRaw(format_string('@block %name has been created.', array(
      '@block' => 'Accordion block',
      '%name' => 'Test Block'
    )), 'Accordion block created.');

    //$this->drupalPostForm(NULL, NULL, t('Save block'));
    
    //$this->drupalPlaceBlock('testblock', array('id' => 'test_block', 'label' => 'Test Block', 'region' => 'sidebar_first'));
    
    //$this->drupalGet('/node');
    //$block = $this->xpath('//div[@id=sidebar-first]/div[contains(@class, accordion_blocks_container)]');
    //$this->assertTrue(!empty($block));
    
  }
  
}
