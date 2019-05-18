<?php

namespace Drupal\matomo\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test user fields functionality of Matomo module.
 *
 * @group Matomo
 */
class MatomoUserFieldsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['matomo', 'field_ui'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer user form display',
      'opt-in or out of matomo tracking',
    ];

    // User to set up matomo.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests if "allow users to customize tracking on their account page" works.
   */
  public function testMatomoUserFields() {
    $site_id = '1';
    $this->config('matomo.settings')->set('site_id', $site_id)->save();
    $this->config('matomo.settings')->set('url_http', 'http://www.example.com/matomo/')->save();
    $this->config('matomo.settings')->set('url_https', 'https://www.example.com/matomo/')->save();

    // Check if the pseudo field is shown on account forms.
    $this->drupalGet('admin/config/people/accounts/form-display');
    $this->assertResponse(200);
    $this->assertRaw(t('Matomo settings'), '[testMatomoUserFields]: Matomo settings field exists on Manage form display.');

    // No customization allowed.
    $this->config('matomo.settings')->set('visibility.user_account_mode', 0)->save();
    $this->drupalGet('user/' . $this->admin_user->id() . '/edit');
    $this->assertResponse(200);
    $this->assertNoRaw(t('Matomo settings'), '[testMatomoUserFields]: Matomo settings field does not exist on user edit page.');

    // Tracking on by default, users with opt-in or out of matomo tracking permission
    // can opt out.
    $this->config('matomo.settings')->set('visibility.user_account_mode', 1)->save();
    $this->drupalGet('user/' . $this->admin_user->id() . '/edit');
    $this->assertResponse(200);
    $this->assertRaw(t('Users are tracked by default, but you are able to opt out.'), '[testMatomoUserFields]: Matomo settings field exists on on user edit page');

    // Tracking off by default, users with opt-in or out of matomo tracking permission
    // can opt in.
    $this->config('matomo.settings')->set('visibility.user_account_mode', 2)->save();
    $this->drupalGet('user/' . $this->admin_user->id() . '/edit');
    $this->assertResponse(200);
    $this->assertRaw(t('Users are <em>not</em> tracked by default, but you are able to opt in.'), '[testMatomoUserFields]: Matomo settings field exists on on user edit page.');
  }

}
