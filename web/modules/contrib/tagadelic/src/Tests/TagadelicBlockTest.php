<?php

namespace Drupal\tagadelic\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Config\Schema\SchemaIncompleteException;

/**
 * Tests for displaying tagadelic block.
 *
 * @group tagadelic
 */
class TagadelicBlockTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('tagadelic', 'taxonomy', 'block');

  /**
   * Test block placement.
   */
  function testTagadelicBlock() {
    // Create user.
    $web_user = $this->drupalCreateUser(array('administer blocks'));
    // Login the admin user.
    $this->drupalLogin($web_user);

    $theme_name = \Drupal::config('system.theme')->get('default');
  
    // Verify the block is listed to be added.
    $this->drupalGet('/admin/structure/block/library/' . $theme_name);
    $this->assertRaw(t('Tagadelic tag cloud'), 'Block label found.');
   
    $settings = array(
      'label' => t('Tagadelic tag cloud test'),
      'id' => 'tagadelic_block',
      'theme' => $theme_name,
      'num_tags_block' => 5,
    );
    $this->drupalPlaceBlock('tagadelic_block', $settings);
    
    // Verify that block is there. 
    $this->drupalGet('');
    $this->assertRaw($settings['label'], 'Block found.');
  }
}
