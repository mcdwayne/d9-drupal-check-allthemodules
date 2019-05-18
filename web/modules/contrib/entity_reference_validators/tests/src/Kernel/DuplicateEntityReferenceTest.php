<?php

namespace Drupal\Tests\entity_reference_validators\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\Core\Entity\Element\EntityAutocomplete;

/**
 * Tests duplicates of entity reference validation.
 *
 * @group entity_reference
 */
class DuplicateEntityReferenceTest extends FieldKernelTestBase {

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
  public static $modules = ['entity_reference_validators'];

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();

    // Use the util to create an instance.
    $this->createEntityReferenceField('entity_test', 'entity_test', 'field_test_entity_test', 'Test entity reference', 'entity_test', 'default', [], -1);
    $this->field = FieldConfig::loadByName('entity_test', 'entity_test', 'field_test_entity_test');
    $this->field->setThirdPartySetting('entity_reference_validators', 'duplicate_reference', TRUE)->save();
  }

  /**
   * Tests circular references.
   */
  public function testCircularReference() {
    // Create test entities.
    $entity1 = EntityTest::create();
    $entity1->save();

    $entity2 = EntityTest::create();
    $entity2->save();

    // Create a test entity that references other entities.
    $entity3 = EntityTest::create();
    $entity3->field_test_entity_test[] = [
      ['target_id' => $entity1->id()],
      ['target_id' => $entity2->id()],
    ];
    $entity3->save();
    $errors = $entity3->validate();
    $this->assertCount(0, $errors);

    // Make the third entity reference the same entity twice.
    $entity3 = EntityTest::create();
    $entity3->field_test_entity_test = [
      ['target_id' => $entity2->id()],
      ['target_id' => $entity2->id()],
    ];
    $entity3->save();
    $errors = $entity3->validate();
    $this->assertCount(2, $errors);
    $this->assertEquals(new FormattableMarkup('The value %label has been entered multiple times.', ['%label' => EntityAutocomplete::getEntityLabels([$entity2])]), $errors[0]->getMessage());
    $this->assertEquals('field_test_entity_test.0.target_id', $errors[0]->getPropertyPath());

    // Ensure the validator is configurable.
    $this->field->setThirdPartySetting('entity_reference_validators', 'duplicate_reference', FALSE)->save();

    // Reload the entity refresh field definitions.
    $entity3 = EntityTest::create();
    $entity3->field_test_entity_test = [
      ['target_id' => $entity2->id()],
      ['target_id' => $entity2->id()],
    ];
    $errors = $entity3->validate();
    $this->assertCount(0, $errors);
  }

}
