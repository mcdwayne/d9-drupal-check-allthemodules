<?php

namespace Drupal\Tests\role_menu\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group role_menu
 */
class UserRoleCollectionTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['eabax_core', 'role_menu'];

  /**
   * Tests role collection page.
   */
  public function testRoleCollection() {
    $user = $this->drupalCreateUser([
      'administer menu',
      'administer permissions',
    ]);
    $this->drupalLogin($user);

    $assert_session = $this->assertSession();

    $this->drupalGet(Url::fromRoute('entity.user_role.collection'));
    $assert_session->statusCodeEquals(200);
    $assert_session->linkExists(t('Edit role menu'));

    $this->clickLink(t('Edit role menu'));
    $assert_session->statusCodeEquals(200);

    $this->drupalPostForm(NULL, [], t('Save'));
    $assert_session->statusCodeEquals(200);
  }

}
