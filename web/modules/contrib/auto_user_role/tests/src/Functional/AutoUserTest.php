<?php

namespace Drupal\Tests\auto_user_role\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group auto_user_role
 */
class AutoUserTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['auto_user_role'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * The entity field manager
   *
   * @var EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->user);
    $this->roleStorage = \Drupal::service('entity.manager')->getStorage('user_role');
    $this->entityFieldManager = \Drupal::service('entity_field.manager');
  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testLoad() {
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertResponse(200);
  }

  /**
   * Test admin collection page
   */
  public function testAdmin(){
    $this->drupalLogin($this->user);
    $this->drupalGet("admin/structure/auto_role_entity");
    $this->assertResponse(200);
  }

  /**
   * Tests the config form.
   */
  public function testConfigForm()
  {
    // Login.
    $this->drupalLogin($this->user);

    // Access config page.
    $this->drupalGet('admin/structure/auto_role_entity/add');
    $this->assertResponse(200);
    $this->assertFieldByName("label");
    $this->assertFieldByName("role");
    $this->assertFieldByName("field");
    $this->assertFieldByName("field_value");

  }

}
