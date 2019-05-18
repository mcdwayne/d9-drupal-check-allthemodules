<?php

namespace Drupal\Tests\disable_user_1_edit\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests the admin functionality of the module.
 *
 * Not strictly needed as a JavaScript test, but since I want to see how this
 * thing works, I might as well try to learn it?
 *
 * @group disable_user_1_edit
 */
class DisableUser1EditIntegrationTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['disable_user_1_edit'];

  /**
   * Tests if module works as expected before and after we toggle the disable.
   */
  public function testDisableToggle() {
    $admin_user = $this->drupalCreateUser([
      'administer users',
      'administer disable user 1 edit',
    ]);
    $this->drupalLogin($admin_user);
    $this->drupalGet('/user/1/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/admin/config/people/disable_user_1_edit');

    // Test that it is possible to toggle it off, and that this has the desired
    // effect.
    $page = $this->getSession()->getPage();
    $page->fillField('disabled', TRUE);
    $page->pressButton('op');
    $this->assertSession()->statusCodeEquals(200);
  }

}
