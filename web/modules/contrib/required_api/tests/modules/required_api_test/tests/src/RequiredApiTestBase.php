<?php

/**
 * @file
 * Contains \Drupal\required_api_test\Tests\RequiredApiTestBase.
 */

namespace Drupal\required_api_test\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Provides common functionality for the Field UI test classes.
 */
abstract class RequiredApiTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'field_ui', 'field_test', 'required_api', 'required_api_test');

  function setUp() {
    parent::setUp();

    // Create test user.
    $admin_user = $this->drupalCreateUser(array('access content', 'administer content types', 'administer node fields', 'administer node form display', 'administer node display', 'administer users', 'administer account settings', 'administer user display', 'bypass node access', 'administer required settings'));
    $this->drupalLogin($admin_user);

    // Create Article node type.
    $this->type = 'article';
    $this->type_label = 'Article';
    $this->drupalCreateContentType(array('type' => $this->type, 'name' => $this->type_label));
  }
}
