<?php

namespace Drupal\Tests\config_view\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Settings form test.
 *
 * @group config_view
 */
class SettingsFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['config_view', 'block'];

  /**
   * The test user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $admin;

  /**
   * Test the settings form submits properly.
   */
  public function testForm() {
    $this->admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->admin);

    $this->drupalGet('/admin/structure/views/settings/config_view');

    // Load all config entities.
    $edit = [
      'edit-data-block' => TRUE,
    ];
    // Enable block.
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $config = \Drupal::config('config_view.settings')->get('data');
    // Verify that the block got enabled.
    $this->assertTrue('block', $config['block']);
  }

}
