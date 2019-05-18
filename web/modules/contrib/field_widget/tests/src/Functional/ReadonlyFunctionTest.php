<?php

namespace Drupal\Tests\field_widget\Functional;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * @coversDefaultClass \Drupal\field_widget\Plugin\Field\FieldWidget\Readonly
 * @group field_widget
 */
class ReadonlyFunctionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field_widget', 'entity_test', 'field', 'entity_reference_revisions'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $string_field_storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'test_string',
      'type' => 'string',
    ]);
    $string_field_storage->save();

    $string_field = FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'test_string',
      'bundle' => 'entity_test',
    ]);
    $string_field->save();

    $integer_field_storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'test_integer',
      'type' => 'integer',
    ]);
    $integer_field_storage->save();

    $integer_field = FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'test_integer',
      'bundle' => 'entity_test',
    ]);
    $integer_field->save();

    $entity_reference_field_storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'test_entity_reference',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'entity_test_mulrev',
      ]
    ]);
    $entity_reference_field_storage->save();

    $entity_reference_field = FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'test_entity_reference',
      'bundle' => 'entity_test',
    ]);
    $entity_reference_field->save();

    $entity_reference_revisions_field_storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'test_entity_reference_revisions',
      'type' => 'entity_reference_revisions',
      'settings' => [
        'target_type' => 'entity_test_mulrev',
      ]
    ]);
    $entity_reference_revisions_field_storage->save();

    $entity_reference_revisions_field = FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'test_entity_reference_revisions',
      'bundle' => 'entity_test',
    ]);
    $entity_reference_revisions_field->save();

    $form_display = entity_get_form_display('entity_test', 'entity_test', 'default');
    $form_display->setComponent('test_string', [
      'type' => 'readonly',
    ]);
    $form_display->setComponent('test_integer', [
      'type' => 'readonly',
    ]);
    $form_display->setComponent('test_entity_reference', [
       'type' => 'readonly_entity_reference',
    ]);
    $form_display->setComponent('test_entity_reference_revisions', [
      'type' => 'readonly_entity_reference',
    ]);
    $form_display->save();

    $account = $this->drupalCreateUser(['administer entity_test content']);
    $this->drupalLogin($account);
  }

  public function testString() {
    // Precreate an entity with an existing value.
    $entity = EntityTest::create([
      'type' => 'entity_test',
      'test_string' => 'value',
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl('edit-form'));

    $page = $this->getSession()->getPage();

    $string_field = $page->findField('edit-test-string-0-value');
    $this->assertTrue($string_field->hasAttribute('disabled'));

    $this->submitForm([
      'test_string[0][value]' => 'other value',
    ], 'Save');

    $entity = EntityTest::load($entity->id());
    $this->assertEquals('value', $entity->test_string->value);
  }

  public function testNumeric() {
    // Precreate an entity with an existing value.
    $entity = EntityTest::create([
      'type' => 'entity_test',
      'test_integer' => 123,
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl('edit-form'));

    $page = $this->getSession()->getPage();

    $integer_field = $page->findField('edit-test-integer-0-value');
    $this->assertTrue($integer_field->hasAttribute('disabled'));

    $this->submitForm([
      'test_integer[0][value]' => 124,
    ], 'Save');

    $entity = EntityTest::load($entity->id());
    $this->assertEquals(123, $entity->test_integer->value);
  }

  public function testEntityReference() {
    $referenced_entity = EntityTestMulRev::create([
      'type' => 'entity_test_mulrev',
    ]);
    $referenced_entity->save();
    $referenced_entity2 = EntityTestMulRev::create([
      'type' => 'entity_test_mulrev',
    ]);
    $referenced_entity2->save();

    // Precreate an entity with an existing value.
    $entity = EntityTest::create([
      'type' => 'entity_test',
      'test_entity_reference' => $referenced_entity,
    ]);
    $entity->save();

    $this->drupalGet($entity->toUrl('edit-form'));

    $page = $this->getSession()->getPage();
    file_put_contents('/tmp/output.html', $page->getHtml());

    $integer_field = $page->findField('edit-test-entity-reference-0-target-id');
    $this->assertTrue($integer_field->hasAttribute('disabled'));

    $this->submitForm([
      'test_entity_reference[0][target_id]' => "a ({$referenced_entity2->id()})",
    ], 'Save');

    $entity = EntityTest::load($entity->id());
    $this->assertEquals($referenced_entity->id(), $entity->test_entity_reference->target_id);
  }

  public function testEntityReferenceRevisions() {
    $referenced_entity = EntityTestMulRev::create([
      'name' => 'test entity',
      'type' => 'entity_test_mulrev',
    ]);
    $referenced_entity->save();
    $referenced_entity_rev = clone $referenced_entity;
    $referenced_entity_rev->setNewRevision(TRUE);
    $referenced_entity_rev->save();

    // Precreate an entity with an existing value.
    $entity = EntityTest::create([
      'type' => 'entity_test',
      'test_entity_reference_revisions' => $referenced_entity,
    ]);
    $entity->save();
    $entity = EntityTest::load($entity->id());
    $this->assertEquals($referenced_entity->getRevisionId(), $entity->test_entity_reference_revisions->entity->getRevisionId());

    $this->drupalGet($entity->toUrl('edit-form'));

    $page = $this->getSession()->getPage();
    file_put_contents('/tmp/output.html', $page->getHtml());

    $integer_field = $page->findField('edit-test-entity-reference-revisions-0-target-id');
    $this->assertTrue($integer_field->hasAttribute('disabled'));

    $this->submitForm([
      'test_entity_reference_revisions[0][target_id]' => "a ({$referenced_entity_rev->getRevisionId()})",
    ], 'Save');

    $entity = EntityTest::load($entity->id());
    $this->assertEquals($referenced_entity->getRevisionId(), $entity->test_entity_reference_revisions->target_revision_id);
  }

}
