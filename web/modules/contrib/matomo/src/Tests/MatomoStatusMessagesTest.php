<?php

namespace Drupal\matomo\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test status messages functionality of Matomo module.
 *
 * @group Matomo
 */
class MatomoStatusMessagesTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['matomo', 'matomo_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer matomo',
    ];

    // User to set up matomo.
    $this->admin_user = $this->drupalCreateUser($permissions);
  }

  /**
   * Tests if status messages tracking is properly added to the page.
   */
  public function testMatomoStatusMessages() {
    $site_id = '1';
    $this->config('matomo.settings')->set('site_id', $site_id)->save();
    $this->config('matomo.settings')->set('url_http', 'http://www.example.com/matomo/')->save();
    $this->config('matomo.settings')->set('url_https', 'https://www.example.com/matomo/')->save();

    // Enable logging of errors only.
    $this->config('matomo.settings')->set('track.messages', ['error' => 'error'])->save();

    $this->drupalPostForm('user/login', [], t('Log in'));
    $this->assertRaw('_paq.push(["trackEvent", "Messages", "Error message", "Username field is required."]);', '[testMatomoStatusMessages]: trackEvent "Username field is required." is shown.');
    $this->assertRaw('_paq.push(["trackEvent", "Messages", "Error message", "Password field is required."]);', '[testMatomoStatusMessages]: trackEvent "Password field is required." is shown.');

    // Testing this Drupal::messenger() requires an extra test module.
    $this->drupalGet('matomo-test/drupal-messenger-add-message');
    $this->assertNoRaw('_paq.push(["trackEvent", "Messages", "Status message", "Example status message."]);', '[testMatomoStatusMessages]: Example status message is not enabled for tracking.');
    $this->assertNoRaw('_paq.push(["trackEvent", "Messages", "Warning message", "Example warning message."]);', '[testMatomoStatusMessages]: Example warning message is not enabled for tracking.');
    $this->assertRaw('_paq.push(["trackEvent", "Messages", "Error message", "Example error message."]);', '[testMatomoStatusMessages]: Example error message is shown.');
    $this->assertRaw('_paq.push(["trackEvent", "Messages", "Error message", "Example error message with html tags and link."]);', '[testMatomoStatusMessages]: HTML has been stripped successful from Example error message with html tags and link.');

    // Enable logging of status, warnings and errors.
    $this->config('matomo.settings')->set('track.messages', [
      'status' => 'status',
      'warning' => 'warning',
      'error' => 'error',
    ])->save();

    $this->drupalGet('matomo-test/drupal-messenger-add-message');
    $this->assertRaw('_paq.push(["trackEvent", "Messages", "Status message", "Example status message."]);', '[testMatomoStatusMessages]: Example status message is enabled for tracking.');
    $this->assertRaw('_paq.push(["trackEvent", "Messages", "Warning message", "Example warning message."]);', '[testMatomoStatusMessages]: Example warning message is enabled for tracking.');
    $this->assertRaw('_paq.push(["trackEvent", "Messages", "Error message", "Example error message."]);', '[testMatomoStatusMessages]: Example error message is shown.');
    $this->assertRaw('_paq.push(["trackEvent", "Messages", "Error message", "Example error message with html tags and link."]);', '[testMatomoStatusMessages]: HTML has been stripped successful from Example error message with html tags and link.');
  }

}
