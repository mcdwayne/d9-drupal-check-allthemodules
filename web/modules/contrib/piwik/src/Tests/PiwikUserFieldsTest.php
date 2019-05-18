<?php

namespace Drupal\piwik\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test user fields functionality of Piwik module.
 *
 * @group Piwik
 */
class PiwikUserFieldsTestTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['piwik', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer user form display',
      'opt-in or out of piwik tracking',
    ];

    // User to set up piwik.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests if "allow users to customize tracking on their account page" works.
   */
  public function testPiwikUserFields() {
    $ua_code = 'UA-123456-1';
    $this->config('piwik.settings')->set('account', $ua_code)->save();

    // Check if the pseudo field is shown on account forms.
    $this->drupalGet('admin/config/people/accounts/form-display');
    $this->assertResponse(200);
    $this->assertRaw(t('Piwik settings'), '[testPiwikUserFields]: Piwik settings field exists on Manage form display.');

    // No customization allowed.
    $this->config('piwik.settings')->set('visibility.user_account_mode', 0)->save();
    $this->drupalGet('user/' . $this->admin_user->id() . '/edit');
    $this->assertResponse(200);
    $this->assertNoRaw(t('Piwik settings'), '[testPiwikUserFields]: Piwik settings field does not exist on user edit page.');

    // Tracking on by default, users with opt-in or out of tracking permission
    // can opt out.
    $this->config('piwik.settings')->set('visibility.user_account_mode', 1)->save();
    $this->drupalGet('user/' . $this->admin_user->id() . '/edit');
    $this->assertResponse(200);
    $this->assertRaw(t('Users are tracked by default, but you are able to opt out.'), '[testPiwikUserFields]: Piwik settings field exists on on user edit page');

    // Tracking off by default, users with opt-in or out of tracking permission
    // can opt in.
    $this->config('piwik.settings')->set('visibility.user_account_mode', 2)->save();
    $this->drupalGet('user/' . $this->admin_user->id() . '/edit');
    $this->assertResponse(200);
    $this->assertRaw(t('Users are <em>not</em> tracked by default, but you are able to opt in.'), '[testPiwikUserFields]: Piwik settings field exists on on user edit page.');
  }

}
