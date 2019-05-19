<?php

namespace Drupal\Tests\simple_ldap\Functional;

use Drupal\Core\Url;
use Drupal\simple_ldap\SimpleLdap;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Simple LDAP configuration options.
 *
 * @group simple_ldap
 */
class SimpleLdapTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'simple_ldap',
  ];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'access administration pages',
    'administer site configuration',
  ];

  /**
   * An user with administration permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Configure variables to test with a free online LDAP test server. See:
   * http://www.forumsys.com/en/tutorials/integration-how-to/ldap/online-ldap-test-server
   */
  protected $host = 'ldap.forumsys.com';
  protected $port = 389;
  protected $binddn = 'cn=read-only-admin,dc=example,dc=com';
  protected $bindpw = 'password';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    if (!extension_loaded('ldap')) {
      $this->markTestSkipped("Skipping because the PHP LDAP extension is not enabled.");
    }
    parent::setUp();
    // Login as an admin user.
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test the Simple LDAP server configuration.
   */
  public function testSimpleLdapServerConfiguration() {

    // Check that the server is not connected.
    $ldap_config_url = Url::fromRoute("simple_ldap.server")->toString();
    $this->drupalGet($ldap_config_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Not connected');

    // Test by connecting to a free online LDAP test server. See:
    // http://www.forumsys.com/en/tutorials/integration-how-to/ldap/online-ldap-test-server/
    $edit = [
      'host' => $this->host,
      'port' => $this->port,
      'binddn' => $this->binddn,
      'bindpw' => $this->bindpw,
    ];
    $this->submitForm($edit, t('Save'));

    // Check that the server is connected.
    $this->drupalGet($ldap_config_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Successfully binded to');

    // Configure non-existent LDAP server.
    $edit = [
      'host' => 'not.a.real.ldap.server',
      'port' => 123,
      'binddn' => 'fake',
      'bindpw' => 'credentials',
    ];
    $this->submitForm($edit, t('Save'));

    // Check that the server is does not connect.
    $this->drupalGet($ldap_config_url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Could not bind to not.a.real.ldap.server');
  }

  /**
   * Test the SimpleLdap class.
   */
  public function testSimpleLdapClass() {

    // Add configuration required by SimpleLdap::connect().
    $config = \Drupal::configFactory()->getEditable('simple_ldap.server');
    $config->set('host', $this->host);
    $config->set('port', $this->port);
    $config->save();

    // Test SimpleLdap::connect() method.
    $server = \Drupal::service('simple_ldap.ldap_wrapper');
    $server->connect();
    // Test that the server is connected.
    $this->assertEqual("simple_ldap.ldap_wrapper", $server->_serviceId);
    // Test that the server is unbound.
    $this->assertEqual(FALSE, $server->isBound());

    // Test SimpleLdap::ldapBind().
    $server->ldapBind($this->binddn, $this->bindpw);
    $this->assertEqual(TRUE, $server->isBound());

    // Test SimpleLdap::ldapSearch() and SimpleLdap::getEntries().
    $base_dn = 'dc=example,dc=com';
    $search_filter = 'uid=newton';
    $attributes = array();
    $search_results = $server->ldapSearch($base_dn, $search_filter, $attributes);
    $search_info = $server->getEntries($search_results);
    $this->assertEqual(1, $search_info['count']);

    // Test SimpleLdap::ldapRead().
    $object_class_filter = 'objectClass=*';
    $read_results = $server->ldapRead($base_dn, $object_class_filter, $attributes);
    $read_info = $server->getEntries($read_results);
    $this->assertEqual(1, $read_info['count']);

    // Test SimpleLdap::ldapList().
    $list_filter = "ou=mathematicians";
    $list_results = $server->ldapList($base_dn, $list_filter, $attributes);
    $list_info = $server->getEntries($list_results);
    $this->assertEqual(1, $list_info['count']);
  }
}
