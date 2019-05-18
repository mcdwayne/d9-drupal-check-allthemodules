<?php

namespace Drupal\Tests\dream_fields\Functional;

use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the permissions.
 *
 * @group dream_fields
 */
class AccessTest extends BrowserTestBase {

  use ContentTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'dream_fields',
    'entity_test',
    'node',
    'field_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createContentType(['type' => 'page']);
  }

  /**
   * Test the user interface works.
   */
  public function testUi() {
    $this->visitUiWithPermissions([]);
    $this->assertSession()->statusCodeEquals(403);

    $this->visitUiWithPermissions(['access dream fields']);
    $this->assertSession()->statusCodeEquals(200);

    $this->visitUiWithPermissions(['access dream fields', 'use text_single dream field']);
    $this->assertSession()->pageTextContains('Single line of text');
    $this->assertSession()->pageTextNotContains('Multiples lines of text');

    $this->visitUiWithPermissions(['access dream fields', 'use all dream fields']);
    $this->assertSession()->pageTextContains('Single line of text');
    $this->assertSession()->pageTextContains('Multiples lines of text');
  }

  /**
   * Login to the site with the given permissions.
   *
   * @param array $permissions
   *   The permissions array.
   */
  protected function visitUiWithPermissions($permissions) {
    $this->drupalLogin($this->drupalCreateUser(array_merge([
      'administer content types',
      'administer nodes',
      'bypass node access',
      'administer node fields',
    ], $permissions)));
    $this->drupalGet('admin/structure/types/manage/page/fields/add-field-simple');
  }

}
