<?php

namespace Drupal\Tests\vex_message\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests vex_message change form.
 *
 * @group vex_message
 */
class VexMessageSettingsFormTest extends BrowserTestBase {

  /**
   * Admin user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Default user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $defaultUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'vex_message',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->createUser(['administer vex message']);
    $this->defaultUser = $this->createUser();
  }

  /**
   * Tests that settings form works as expected.
   */
  public function testVexMessageSettingsForm() {
    // Test adding extra info.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/user-interface/vex-message');
    $this->assertSession()->statusCodeEquals(200);

    $edit = [
      'vex_message_status' => 1,
      'theme' => 'vex-theme-default',
      'title' => 'Hi there!',
      'body' => 'Some message text!',
      'cookie' => 1,
    ];

    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    $this->assertSession()->pageTextContains('The configuration options have been saved.');
  }

}
