<?php

namespace Drupal\Tests\simple_integrations\Functional;

/**
 * Test the configuration and admin forms.
 *
 * @group simple_integrations
 */
class IntegrationAdminTests extends SimpleIntegrationsTestBase {

  /**
   * Modules to enable.
   *
   * @var arrray
   */
  public static $modules = ['simple_integrations'];

  /**
   * A test user.
   *
   * @var Drupal\Core\Session\AccountInterface
   */
  private $testUser;

  /**
   * The role ID of an editor role.
   *
   * This role can only view integrations but cannot modify or test them.
   *
   * @var string
   */
  private $editorRole;

  /**
   * The role ID of an administrator role.
   *
   * This role can view integrations, edit and test integrations.
   *
   * @var string
   */
  private $adminRole;

  /**
   * An integration.
   *
   * @var \Drupal\simple_integrations\IntegrationInterface
   */
  public $integration;

  /**
   * Run setup tasks.
   */
  public function setUp() {
    parent::setUp();

    // Create user.
    $this->testUser = $this->drupalCreateUser();

    // Create some test roles.
    $this->editorRole = $this->drupalCreateRole(['view integrations']);
    $this->adminRole = $this->drupalCreateRole([
      'view integrations',
      'administer integrations',
      'test integration connections',
    ]);

    // Create an integration.
    $entity_storage = \Drupal::entityTypeManager()->getStorage('integration');
    $integration = $entity_storage->create($this->getDefaultConfig());
    $integration->save();
    $this->integration = $integration;
  }

  /**
   * Test that the integration list page cannot be reached by an anonymous user.
   */
  public function testIntegrationListAccessAnonymousUsers() {
    // Test that it can't be accessed by anonymous users.
    $this->drupalGet('admin/config/integrations');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test that the integration list page cannot be reached by a non-admin user.
   */
  public function testIntegrationListAccessNonAdminUsers() {
    // Ensure the non-admin user can't access the page.
    $this->drupalLogin($this->testUser);
    $this->drupalGet('admin/config/integrations');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogout($this->testUser);
  }

  /**
   * Test that the integration list page can be reached by an admin user.
   */
  public function testIntegrationListAccessEditorUsers() {
    // Add the editor role to the test user.
    $this->testUser->addRole($this->editorRole);
    $this->testUser->save();
    $this->drupalLogin($this->testUser);

    // Editors can access the integration list.
    $this->drupalGet('admin/config/integrations');
    $this->assertSession()->statusCodeEquals(200);

    // Editors can't access the test connections page.
    $test_connection_url = 'admin/config/integrations/' . $this->integration->id() . '/test-connection';
    $this->drupalGet($test_connection_url, ['integration' => $this->integration]);
    $this->assertSession()->statusCodeEquals(403);

    // They also shouldn't be able to edit the integration.
    $edit_integration_url = 'admin/config/integrations/' . $this->integration->id();
    $this->drupalGet($edit_integration_url);
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogout($this->testUser);
  }

  /**
   * Test that the integration can be edited and tested by an admin user.
   */
  public function testIntegrationListAccessAdminUsers() {
    // Add the admin role to the test user.
    $this->testUser->addRole($this->adminRole);
    $this->testUser->save();
    $this->drupalLogin($this->testUser);

    // Admins can access the integration list.
    $this->drupalGet('admin/config/integrations');
    $this->assertSession()->statusCodeEquals(200);

    // Admins can access the test connections page.
    $test_connection_url = 'admin/config/integrations/' . $this->integration->id() . '/test-connection';
    $this->drupalGet($test_connection_url, ['integration' => $this->integration]);
    $this->assertSession()->statusCodeEquals(200);

    // Admins can edit the integrations.
    $edit_integration_url = 'admin/config/integrations/' . $this->integration->id();
    $this->drupalGet($edit_integration_url);
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogout($this->testUser);
  }

}
