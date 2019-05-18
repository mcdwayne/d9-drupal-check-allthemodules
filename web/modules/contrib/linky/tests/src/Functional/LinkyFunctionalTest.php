<?php

namespace Drupal\Tests\linky\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Defines a class for testing linky functionality.
 *
 * @group linky
 */
class LinkyFunctionalTest extends BrowserTestBase {

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'link',
    'linky',
    'user',
    'dynamic_entity_reference',
    'field_ui',
    'entity_test',
    'views',
  ];

  /**
   * Permissions to grant admin user.
   *
   * @var array
   */
  protected $permissions = [
    'access administration pages',
    'view test entity',
    'administer entity_test fields',
    'administer entity_test display',
    'administer entity_test form display',
    'administer entity_test content',
    'add linky entities',
    'edit linky entities',
    'view linky entities',
  ];

  /**
   * Sets the test up.
   */
  protected function setUp() {
    parent::setUp();
    // Test admin user.
    $this->adminUser = $this->drupalCreateUser($this->permissions);
  }

  /**
   * Tests admin UI.
   */
  public function testLinkyAdminUI() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/content/linky/add');
    // We test with an external URL to ensure that view builder can render the
    // entity.
    $url = 'http://example.com/test';
    $this->submitForm([
      'link[0][uri]' => $url,
      'link[0][title]' => 'Test',
    ], 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists($url);
  }

}
