<?php

namespace Drupal\Tests\multiversion\Functional;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\multiversion\Plugin\Field\FieldType\RevisionItem;

/**
 * Test the creation and operation of the Revision field.
 *
 * @group multiversion
 */
class RevisionFieldTest extends FieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected $fieldName = '_rev';

  /**
   * {@inheritdoc}
   */
  protected $createdEmpty = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $itemClass = '\Drupal\multiversion\Plugin\Field\FieldType\RevisionItem';

  public function testFieldOperations() {
    foreach ($this->entityTypes as $entity_type_id => $values) {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $entity = $this->createTestEntity($storage, $values);

      // Test normal save operations.

      $this->assertTrue($entity->_rev->new_edit, 'New edit flag is TRUE after creation.');

      $revisions = $entity->_rev->revisions;
      $this->assertTrue((is_array($revisions) && empty($revisions)), 'Revisions property is empty after creation.');
      $this->assertTrue((strpos($entity->_rev->value, '0') === 0), 'Revision index was 0 after creation.');

      $entity->save();
      $first_rev = $entity->_rev->value;
      $this->assertTrue((strpos($first_rev, '1') === 0), 'Revision index was 1 after first save.');

      // Simulate the input from a replication.

      $entity = $this->createTestEntity($storage, $values);
      $sample_rev = RevisionItem::generateSampleValue($entity->_rev->getFieldDefinition());

      $entity->_rev->value = $sample_rev['value'];
      $entity->_rev->new_edit = FALSE;
      $entity->_rev->revisions = [$sample_rev['revisions'][0]];
      $entity->save();
      // Assert that the revision token did not change.
      $this->assertEqual($entity->_rev->value, $sample_rev['value']);

      // Test the is_stub property.

      $entity = $this->createTestEntity($storage, $values);
      $entity->save();
      $entity = $storage->load($entity->id());
      $this->assertIdentical(FALSE, $entity->_rev->is_stub, 'Entity saved normally is loaded as not stub.');

      $entity = $this->createTestEntity($storage, $values);
      $entity->_rev->is_stub = FALSE;
      $entity->save();
      $entity = $storage->load($entity->id());
      $this->assertIdentical(FALSE, $entity->_rev->is_stub, 'Entity saved explicitly as not stub is loaded as not stub.');

      $entity = $this->createTestEntity($storage, $values);
      $entity->_rev->is_stub = TRUE;
      $entity->save();
      $entity = $storage->load($entity->id());
      $this->assertIdentical(TRUE, $entity->_rev->is_stub, 'Entity saved explicitly as stub is loaded as stub.');
      $this->assertEqual($entity->_rev->value, '0-00000000000000000000000000000000', 'Entity has the revision ID of a stub.');
      $entity->_rev->is_stub = FALSE;
      $this->assertFalse($entity->_rev->is_stub, 'Setting an explicit value as not stub works after an entity has been saved.');
    }
  }

  protected function createTestEntity(EntityStorageInterface $storage, array $values) {
    switch ($storage->getEntityTypeId()) {
      case 'block_content':
        $values['info'] = $this->randomMachineName();
        break;
    }
    return $storage->create($values);
  }

}
