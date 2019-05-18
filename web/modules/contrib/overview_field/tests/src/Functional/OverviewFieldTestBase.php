<?php

namespace Drupal\Tests\overview_field\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\overview_field\Kernel\OverviewFieldCreationTrait;

/**
 * This class provides methods specifically for testing overview_field handling.
 */
abstract class OverviewFieldTestBase extends BrowserTestBase {

  use OverviewFieldCreationTrait;

  /**
   * Moudles to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'field_ui', 'overview_field'];

  /**
   * An user with permissions to administer content types.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Setup.
   */
  protected function setUp() {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Basic page',
      ]);
      $this->drupalCreateContentType([
        'type' => 'article',
        'name' => 'Article',
      ]);
    }

    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'access administration pages',
      'administer site configuration',
      'administer content types',
      'administer node fields',
      'administer nodes',
      'create article content',
      'edit any article content',
      'delete any article content',
    ]);
    $this->drupalLogin($this->adminUser);
  }

}
