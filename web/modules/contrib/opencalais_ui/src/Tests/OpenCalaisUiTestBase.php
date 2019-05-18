<?php

namespace Drupal\opencalais_ui\Tests;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Base class for tests.
 */
abstract class OpenCalaisUiTestBase extends WebTestBase {

  use FieldUiTestTrait;

  /**
   * Drupal user object created by loginAsAdmin().
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin_user = NULL;

  /**
   * List of permissions used by loginAsAdmin().
   *
   * @var array
   */
  protected $admin_permissions = [];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'block',
    'opencalais_ui',
    'taxonomy',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->admin_permissions = [
      'administer site configuration',
      'administer nodes',
      'create article content',
      'administer content types',
      'administer node fields',
      'edit any article content',
      'administer opencalais',
      'administer content types'
    ];
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Creates an user with admin permissions and log in.
   *
   * @param array $additional_permissions
   *   Additional permissions that will be granted to admin user.
   * @param bool $reset_permissions
   *   Flag to determine if default admin permissions will be replaced by
   *   $additional_permissions.
   *
   * @return object
   *   Newly created and logged in user object.
   */
  function loginAsAdmin($additional_permissions = [], $reset_permissions = FALSE) {
    $permissions = $this->admin_permissions;

    if ($reset_permissions) {
      $permissions = $additional_permissions;
    }
    elseif (!empty($additional_permissions)) {
      $permissions = array_merge($permissions, $additional_permissions);
    }

    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
    return $this->admin_user;
  }

  /**
   * Set a test API key.
   */
  public function setTestApiKey() {
    $edit = [
      'api_key' => 'test_key'
    ];
    $this->drupalPostForm('admin/config/content/opencalais/general', $edit, 'Save configuration');
  }

  /**
   * Set the Open Calais field.
   * @param string $field_name
   *   Taxonomy field to use for storing the tags.
   */
  public function setTestOpenCalaisField($field_name) {
    $edit = [
      'opencalais_field' => $field_name
    ];
    $this->drupalPostForm('admin/structure/types/manage/article', $edit, 'Save content type');
  }

}
