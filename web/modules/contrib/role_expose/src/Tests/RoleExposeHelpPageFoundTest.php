<?php

namespace Drupal\role_expose\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Make sure help page text populates and is readable only with proper perms.
 *
 * @group Role Expose
 */
class RoleExposeHelpPageFoundTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['role_expose', 'help'];

  /**
   * Make sure admin can read help text.
   */
  public function testAdminUserCanReadHelp() {

    // Create a user and login.
    $account = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($account);

    // Verify Role Expose is listed in help pages.
    $this->drupalGet('admin/help');
    $this->assertLink(t('Role Expose'), 0, t('Check Help page has module name as link.'), t('Role Expose'));
    // Verify Role Expose page has correct content.
    $this->clickLink(t('Role Expose'));
    $this->assertText(t('Role Expose -module gives site administrators ability to expose user their own user roles.'), t('Check Help page has module help test (check beginning of text).'));
  }

}
