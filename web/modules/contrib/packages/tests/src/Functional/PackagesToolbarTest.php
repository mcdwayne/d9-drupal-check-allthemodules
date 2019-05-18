<?php

namespace Drupal\Tests\packages\Functional;

use Drupal\Tests\packages\Functional\PackagesTestBase;

/**
 * Test the Packages toolbar integration.
 *
 * @group packages
 */
class PackagesToolbarTest extends PackagesTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'packages',
    'packages_example_login_greeting',
    'packages_example_page',
    'toolbar',
  ];

  /**
   * A user with toolbar access.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $toolbarUser;

  /**
   * A user with packages access plus toolbar access.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $toolbarPackagesUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a toolbar user.
    $this->toolbarUser = $this->drupalCreateUser(['access toolbar']);

    // Create a packages and toolbar user.
    $this->toolbarPackagesUser = $this->drupalCreateUser([
      'access toolbar',
      'access packages',
      'access packages example page',
    ]);
  }

  /**
   * Tests the toolbar integration.
   */
  public function testToolbar() {
    // Log in as the toolbar user without packages permissions.
    $this->drupalLogin($this->toolbarUser);

    // Packages should not be in the toolbar.
    $this->assertSession()->elementNotExists('css', '#toolbar-item-packages');
    $this->assertSession()->elementNotExists('css', '#toolbar-item-packages-tray');

    // Log in as the toolbar user with packages permissions.
    $this->drupalLogin($this->toolbarPackagesUser);

    // Packages should be present in the toolbar.
    $this->assertSession()->elementExists('css', '#toolbar-item-packages');
    $this->assertSession()->elementExists('css', '#toolbar-item-packages-tray');
    $this->assertSession()->elementExists('css', 'a.edit-packages');

    // Click the manage packages link.
    $this->clickLink(t('Manage packages'));
    $this->assertSession()->addressEquals('packages');

    // Enable both packages.
    $this->submitPackagesForm([
      'example_page' => TRUE,
      'login_greeting' => TRUE,
    ]);

    // There should be a settings link in the toolbar for the login greeting
    // package but not the example page.
    $this->assertSession()->elementExists('css', '#toolbar-item-packages-tray a.package-login_greeting');
    $this->assertSession()->elementNotExists('css', '#toolbar-item-packages-tray a.package-example_page');

    // Click the toolbar link for the login greeting settings.
    $this->clickLink(t('Package example: Login greeting'));
    $this->assertSession()->addressEquals('packages/login_greeting/settings');

    // Disable both packages.
    $this->submitPackagesForm([
      'example_page' => FALSE,
      'login_greeting' => FALSE,
    ]);

    // The settings link should be gone.
    // This also tests clearing the cache upon packages form submission.
    $this->assertSession()->elementNotExists('css', '#toolbar-item-packages-tray a.package-login_greeting');
  }

}
