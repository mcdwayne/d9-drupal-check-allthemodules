<?php

namespace Drupal\Tests\menu_multilingual\Functional;

/**
 * Class MenuMultilingualMenuBlockTest.
 *
 * Tests for Menu Multilingual module integration with menu_block.
 *
 * @group MenuMultilingualMenuBlockTest
 */
class MenuMultilingualMenuBlockTest extends MenuMultilingualTest {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public $menu_block_config_path = 'admin/structure/block/manage/mainnavigation';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
    'menu_multilingual',
    'content_translation',
    'config_translation',
    'views',
    'views_ui',
    'menu_block',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Remove the default main menu and replace it with a menu_block.
    $this->drupalPostForm('admin/structure/block/manage/bartik_main_menu/delete', [], 'Remove');
    $this->drupalPostForm('admin/structure/block/add/menu_block:main/bartik', [
      'id'                                => 'mainnavigation',
      'settings[label]'                   => 'Main navigation',
      'settings[label_display]'           => FALSE,
      'settings[level]'                   => 1,
      'settings[depth]'                   => 0,
      'settings[expand]'                  => 1,
      'settings[suggestion]'              => 'main',
      'settings[only_translated_labels]'  => FALSE,
      'settings[only_translated_content]' => FALSE,
      'region'                            => 'primary_menu',
    ], 'Save block');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Display Menu Multilingual form.
   */
  public function testMenuMultilingualFormDisplay() {
    parent::testMenuMultilingualFormDisplay();
    // Check if menu_block form fields are displayed
    $this->assertSession()->pageTextContains("Advanced options");
    $this->assertSession()->pageTextContains("HTML and style options");
  }

}
