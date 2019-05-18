<?php

namespace Drupal\Tests\admin_user_language\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests BasicForm to alter admin_user_language configuration.
 *
 * @coversDefaultClass \Drupal\admin_user_language\Form\BasicForm
 *
 * @group admin_user_language
 */
class AdminUserLanguageFormPermissionTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user', 'admin_user_language'];

  /**
   * Tests the basic functionality of the field.
   */
  public function testPermissionDenied() {
    $admin_user = $this->drupalCreateUser([
                                            'access administration pages',
                                          ]);
    $this->drupalLogin($admin_user);

    // Display settings form.
    $output = $this->drupalGet('admin/config/admin_user_language/settings');

    $this->assertContains('Access denied', $output);
  }

  /**
   * Tests the basic functionality of the field.
   */
  public function testPermissionGranted() {
    $admin_user = $this->drupalCreateUser([
                                            'access administration pages',
                                            'administer admin interface language'
                                          ]);
    $this->drupalLogin($admin_user);

    // Display settings form.
    $string = $this->drupalGet('admin/config/admin_user_language/settings');
    $this->assertNotContains('Access denied', $string);
  }

}
