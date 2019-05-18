<?php

namespace Drupal\Tests\countdown\Functional;

use Drupal\Tests\BrowserTestBase;

// Use Drupal\block\Entity\Block;.
/**
 * Tests if the Countdown form block is available.
 *
 * @group countdown
 */
class CountdownBlockTest extends BrowserTestBase {

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * An administrative user to configure the test environment.
   *
   * @var string
   */
  protected $adminUser;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['block', 'countdown'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a new user.
    $this->adminUser = $this->drupalCreateUser(['administer blocks']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the visibility settings for the CountdownBlock.
   */
  public function testCountdownBlockVisibility() {
    // Check if the visibility setting is available.
    $default_theme = $this->config('system.theme')->get('default');
    $this->drupalGet('admin/structure/block/add/countdown_block' . '/' . $default_theme);

    $edit = [
      'region' => 'sidebar_first',
    ];
    $this->drupalPostForm('admin/structure/block/add/countdown_block' . '/' . $default_theme, $edit, t('Save block'));
    $this->assertResponse(200);

    $this->assertText(t('Event Name field is required.'), "Make sure you have insert proper event name.");

    $edit2 = [];
    $edit2['settings[countdown_event_name]'] = 'Test Block';
    $edit2['region'] = 'sidebar_first';
    $this->drupalPostForm('admin/structure/block/add/countdown_block' . '/' . $default_theme, $edit2, t('Save block'));
    $this->assertResponse(200);
    $this->assertText(t('The block configuration has been saved.'), "Make sure you have event details properly.");
  }

}
