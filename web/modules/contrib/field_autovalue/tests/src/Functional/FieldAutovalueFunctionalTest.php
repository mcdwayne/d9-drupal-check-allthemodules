<?php

declare(strict_types = 1);

namespace Drupal\Tests\field_autovalue\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the Field Autovalue functionality.
 */
class FieldAutovalueFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field_ui',
    'path',
    'field_autovalue',
    'field_autovalue_test',
  ];

  /**
   * Tests that the field config form has the plugin selection field.
   */
  public function testFieldConfigForm(): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert = $this->assertSession();

    $user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/structure/types/manage/field_autovalue_test/fields/node.field_autovalue_test.field_auto_generated');
    $assert->selectExists('Autovalue plugin');
    $assert->optionExists('Autovalue plugin', 'field_autovalue_test');
    $this->assertEquals('field_autovalue_test', $page->findField('Autovalue plugin')->getValue());

    // There are no plugins for this field type so the select should not exist.
    $this->drupalGet('/admin/structure/types/manage/field_autovalue_test/fields/node.field_autovalue_test.field_condition_1');
    $this->assertNull($page->findField('Autovalue plugin'));
  }

}
