<?php

namespace Drupal\akismet\Tests;

use Drupal\Core\Logger\RfcLogLevel;

/**
 * Confirm that there is a working key pair and that this status is correctly
 * indicated on the module settings page for appropriate users.
 *
 * @group akismet
 */
class AccessTest extends AkismetTestBase {

  const MESSAGE_SAVED = 'The configuration options have been saved.';
  const MESSAGE_INVALID = 'The configured Akismet API key is invalid.';
  const MESSAGE_NOT_CONFIGURED = 'The Akismet API key is not configured yet.';

  /**
   * Modules to enable.
   * @var array
   */
  public static $modules = ['dblog', 'akismet', 'node', 'comment', 'akismet_test_server'];

  protected $createKeys = FALSE;
  protected $useLocal = TRUE;

  function setUp() {
    parent::setUp();
    \Drupal::configFactory()->getEditable('akismet.settings')->set('test_mode.enabled', FALSE)->save();
  }

  /**
   * Configure an invalid key pair and ensure error message.
   */
  function testAPIKey() {
    // No error message or watchdog messages should be thrown with default
    // testing keys.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/akismet/settings');

    $this->assertText(self::MESSAGE_NOT_CONFIGURED);
    $this->assertNoText(t(self::MESSAGE_SAVED));
    $this->assertNoText(t(self::MESSAGE_INVALID));

    // Set up an invalid test key and check that an error message is shown.
    $edit = array(
      'api_key' => 'foo',
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'), array('watchdog' => RfcLogLevel::EMERGENCY));
    $this->assertText(t(self::MESSAGE_SAVED));
    $this->assertText(t(self::MESSAGE_INVALID));
    $this->assertNoText(t(self::MESSAGE_NOT_CONFIGURED));
  }

  /**
   * Make sure that the Akismet settings page works for users with the
   * 'administer akismet' permission but not those without
   * it.
   */
  function testAdminAccessRights() {
    // Check access for a user that only has access to the 'administer
    // site configuration' permission. This user should have access to
    // the Akismet settings page.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/akismet');
    $this->assertResponse(200);

    // Check access for a user that has everything except the 'administer
    // akismet' permission. This user should not have access to the Akismet
    // settings page.
    $web_user = $this->drupalCreateUser(array_diff(\Drupal::moduleHandler()->invokeAll('perm'), array('administer akismet')));
    $this->drupalLogin($web_user);
    $this->drupalGet('admin/config/content/akismet', array('watchdog' => RfcLogLevel::WARNING));
    $this->assertResponse(403);
  }
} 
