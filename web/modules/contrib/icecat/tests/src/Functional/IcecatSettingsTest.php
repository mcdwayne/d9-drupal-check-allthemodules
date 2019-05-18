<?php

namespace Drupal\Tests\icecat\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests Icecat settings page.
 */
class IcecatSettingsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'icecat',
  ];

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'view the administration theme',
      'access administration pages',
      'administer icecat settings',
      'manage icecat mappings',
    ];

    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the behaviour of the settings page.
   */
  public function testSettingsPage() {
    $this->drupalGet('admin/structure/icecat/settings');

    $form_data = [
      'edit-username' => 'TestUserName',
      'edit-password' => 'TestPassWord',
    ];
    $this->submitForm($form_data, 'edit-submit');

    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Check form fields.
    $this->assertSession()->fieldValueEquals('edit-username', 'TestUserName');
    $this->assertSession()->fieldValueNotEquals('edit-password', 'TestPassWord');
  }

}
