<?php

namespace Drupal\Tests\simple_ldap_user\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Simple LDAP User login.
 *
 * @group simple_ldap_user
 */
class SimpleLdapUserTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'simple_ldap',
    'simple_ldap_user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    if (!extension_loaded('ldap')) {
      $this->markTestSkipped("Skipping because the PHP LDAP extension is not enabled.");
    }

    parent::setUp();

    if (!extension_loaded('ldap')) {
      $this->markTestSkipped("Skipping because the PHP LDAP extension is not enabled.");
    }

    // These test work by connecting to a free online LDAP test server. See:
    // http://www.forumsys.com/en/tutorials/integration-how-to/ldap/online-ldap-test-server/

    // Configure the server settings.
    $server_config = \Drupal::configFactory()->getEditable('simple_ldap.server');
    $server_config->set('host', 'ldap.forumsys.com')
      ->set('port', 389)
      ->set('encryption', 'none')
      ->set('readonly', TRUE)
      ->set('binddn', 'cn=read-only-admin,dc=example,dc=com')
      ->set('bindpw', 'password')
      ->save();

    // Configure the user settings.
    $user_config = \Drupal::configFactory()->getEditable('simple_ldap.user');
    $user_config->set('basedn', 'dc=example,dc=com')
      ->set('user_scope', 'sub')
      ->set('object_class.inetorgperson', 'inetorgperson')
      ->set('object_class.organizationalperson', 'organizationalperson')
      ->set('object_class.person', 'person')
      ->set('object_class.top', 'top')
      ->set('name_attribute', 'uid')
      ->set('mail_attribute', 'mail')
      ->save();
  }

  /**
   * Test the Simple LDAP user login and creation process.
   */
  public function testSimpleLdapUserLogin() {
    // 1. Correct username & correct password.
    // Login as Einstein and confirm that the account was created.
    $user_login_url = Url::fromRoute("user.login")->toString();
    $edit = [
      'name' => 'einstein',
      'pass' => 'password',
    ];
    $this->drupalPostForm($user_login_url, $edit, t('Log in'));
    $this->assertText(t('New user created for einstein.'));

    // 2. Correct username & incorrect password.
    $edit = [
      'name' => 'einstein',
      'pass' => 'incorrect!',
    ];
    $this->drupalPostForm($user_login_url, $edit, t('Log in'));
    $this->assertText(t('Could not authenticate with your username/password in LDAP. Please contact your site administrator.'));

    // 3. Incorrect username & incorrect password.
    $edit = [
      'name' => 'nobodyNotExists',
      'pass' => 'incorrect!',
    ];
    $this->drupalPostForm($user_login_url, $edit, t('Log in'));
    $this->assertText(t('An account could not be found or an ID conflict has been detected. Please contact your site administrator.'));
  }
}
