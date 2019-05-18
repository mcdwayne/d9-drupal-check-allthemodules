<?php

namespace Drupal\auto_login_url\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * AutoLoginUrlTestCase Class.
 *
 * @ingroup Auto Login URL test
 * @group Auto Login URL
 */
class AutoLoginUrlTest extends WebTestBase {

  /**
   * Our module dependencies.
   *
   * @var array
   */
  static public $modules = [
    'auto_login_url',
  ];

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $role = \Drupal\user\Entity\Role::load('authenticated');
    $role->grantPermission('use auto login url');
    $role->save();
    $role = \Drupal\user\Entity\Role::load('anonymous');
    $role->grantPermission('use auto login url');
    $role->save();
    $this->additionalCurlOptions = [CURLOPT_FOLLOWLOCATION => TRUE];
  }

  /**
   * Test token generation.
   */
  public function testAluTokenGenerationCheck() {

    // Create user.
    $user = $this->drupalCreateUser();

    // Create an auto login url for this user.
    $url = auto_login_url_create($user->get('uid')->value, 'user/' . $user->get('uid')->value);

    debug('Generated URL is: ' . $url);

    // Access url.
    $this->drupalGet($url);

    // Make assertions.
    $this->assertResponse(200, t('User logged in successfully.'));
    $this->assertText($user->get('name')->value, t('User name is visible, hence user is logged in.'));

    // Create another user and login again.
    $user2 = $this->drupalCreateUser();

    // Create an auto login url for this user.
    $url = auto_login_url_create($user2->get('uid')->value, 'user/' . $user2->get('uid')->value);

    debug('Generated URL is: ' . $url);

    // Access url.
    $this->drupalGet($url);

    // Make assertions.
    $this->assertResponse(200, t('User 2 logged in successfully.'));
    $this->assertText($user2->get('name')->value, t('User 2 name is visible, hence user is logged in.'));
  }

  /**
   * Test token generation with different settings.
   */
  public function testAluSettingsCheck() {

    // Change settings.
    $config = $this->config('auto_login_url.settings');
    $config->set('secret', 'new secret')->save();
    $config->set('token_length', 8)->save();

    // Create user.
    $user = $this->drupalCreateUser();

    // Create an auto login url for this user.
    $url = auto_login_url_create($user->get('uid')->value, 'user/' . $user->get('uid')->value);

    debug('Generated URL is: ' . $url);

    // Access url.
    $this->drupalGet($url);

    // Make assertions.
    $this->assertResponse(200, t('User logged in successfully.'));
    $this->assertText($user->get('name')->value, t('User name is visible, hence user is logged in.'));
  }

  /**
   * Test flood.
   */
  public function testAluFloodCheck() {

    // Set failed attempts to 5 for easier testing.
    $flood_config = $this->config('user.flood');
    $flood_config->set('ip_limit', 5)->save();

    // Create user.
    $user = $this->drupalCreateUser();

    // Access 10 false URLs. Essentially triggering flood.
    for ($i = 1; $i < 6; $i++) {
      $this->drupalGet('autologinurl/' . $i . '/some-token' . $i);
      $this->assertResponse(403, t('Got access denied page.'));
    }

    // Generate actual auto login url for this user.
    $url = auto_login_url_create($user->get('uid')->value, 'user/' . $user->get('uid')->value);

    debug('Generated URL is: ' . $url);

    // Access url.
    $this->drupalGet($url);

    // Make assertions.
    $this->assertResponse(403, t('Got access denied page.'));
    $this->assertText(t('Sorry, too many failed login attempts from your IP address. This IP address is temporarily blocked. Try again later.'),
      t('Cannot login message visible.'));

    // Clear flood table. I am using sql instead of the flood interface
    // (\Drupal::flood()->clear('user.failed_login_ip');) because it does not
    // seem to work. But it is not a problem at this point since we know the
    // flood records will be on DB anyway.
    $connection = \Drupal::database();
    $connection->truncate('flood')->execute();

    // Try to login again.
    $this->drupalGet($url);
    $this->assertResponse(200, t('User logged in successfully.'));
    $this->assertText($user->get('name')->value, t('User name is visible, hence user is logged in.'));
  }
}
