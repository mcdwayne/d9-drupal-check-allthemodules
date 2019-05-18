<?php

namespace Drupal\Tests\entity_reference_validators\Functional;

use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests duplicates of entity reference validation.
 *
 * @group entity_reference
 */
class DuplicateEntityReferenceTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;

  /**
   * The entity reference field under test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['entity_reference_validators', 'node', 'field_ui'];

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();

    $this->createContentType(['type' => 'test']);
    $this->createEntityReferenceField('node', 'test', 'field_entity_ref_test', 'Test duplicate entity reference', 'node');
    $admin_user = $this->drupalCreateUser(['administer node fields']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests duplicate references in field UI.
   */
  public function testDuplicateReference() {
    // Ensure that the user can't create entity reference to the same entity
    // multiple times.
    $this->drupalGet('admin/structure/types/manage/test/fields/node.test.field_entity_ref_test');
    $this->assertSession()->pageTextContains(t('Reference validators'));
    $this->assertSession()->pageTextContains(t('Prevent entity from referencing duplicates'));
  }

}
