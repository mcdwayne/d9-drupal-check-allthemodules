<?php


namespace Drupal\Tests\seaside_admin_toolbar\Functional;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests Seaside Admin Toolbar functionality.
 *
 * @group seaside_admin_toolbar
 */
class SeasideAdminToolbarTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'admin_toolbar_tools',
    'admin_toolbar',
    'toolbar',
    'toolbar_test',
    'test_page_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a user with permissions to manage.
    $permissions = [
      'administer site configuration',
      'access toolbar',
    ];
    $account = $this->drupalCreateUser($permissions);

    // Initiate user session.
    $this->drupalLogin($account);
  }

  public function testSeasideAdminToolbar() {
    // Get the toolbar test page.
    $this->drupalGet('test-page');
    $this->assertSession()->statusCodeEquals(200);

    // Assert that the seaside_admin_toolbar is present in the HTML.
    $this->assertSession()->responseContains('class="seaside-admin-toolbar');
  }
}