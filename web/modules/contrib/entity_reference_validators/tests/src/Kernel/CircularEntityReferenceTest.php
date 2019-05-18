<?php

namespace Drupal\Tests\entity_reference_validators\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests circular entity reference validation.
 *
 * @group entity_reference
 */
class CircularEntityReferenceTest extends FieldKernelTestBase {

  use EntityReferenceTestTrait;

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

    $this->createEntityReferenceField('entity_test', 'entity_test', 'field_circular_entity_test', 'Circular field', 'entity_test');
    $circularField = FieldConfig::loadByName('entity_test', 'entity_test', 'field_circular_entity_test');
    $circularField->setThirdPartySetting('entity_reference_validators', 'circular_reference', TRUE)->save();

    $this->createEntityReferenceField('entity_test', 'entity_test', 'field_not_circular_entity_test', 'Not circular field', 'entity_test');
    $notCircularField = FieldConfig::loadByName('entity_test', 'entity_test', 'field_not_circular_entity_test');
    $notCircularField->setThirdPartySetting('entity_reference_validators', 'circular_reference', FALSE)->save();
  }

  /**
   * Tests circular references.
   */
  public function testCircularReference() {
    // Create a test entity.
    $entity1 = EntityTest::create();
    $entity1->save();

    // Create a test entity that references the first entity.
    $entity2 = EntityTest::create();
    $entity2->field_circular_entity_test->target_id = $entity1->id();
    $entity2->save();
    $errors = $entity2->validate();
    $this->assertCount(0, $errors);

    // Make the first entity created reference itself.
    $entity1->field_circular_entity_test->target_id = $entity1->id();
    $errors = $entity1->validate();
    $this->assertCount(1, $errors);
    $this->assertEquals(new FormattableMarkup('This entity (%type: %id) cannot be referenced.', ['%type' => 'entity_test', '%id' => $entity1->id()]), $errors[0]->getMessage());
    $this->assertEquals('field_circular_entity_test.0.target_id', $errors[0]->getPropertyPath());

    // Reload the entity refresh field definitions.
    $entity1 = EntityTest::load($entity1->id());
    $entity1->field_not_circular_entity_test->target_id = $entity1->id();
    $errors = $entity1->validate();
    $this->assertCount(0, $errors);
  }

}
