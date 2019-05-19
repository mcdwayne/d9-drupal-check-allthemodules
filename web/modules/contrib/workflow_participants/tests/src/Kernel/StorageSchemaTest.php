<?php

namespace Drupal\Tests\workflow_participants\Kernel;

use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Test workflow_participants storage_schema class.
 *
 * @group workflow_participants
 */
class StorageSchemaTest extends WorkflowParticipantsTestBase {
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    self::$modules[] = 'node_test_config';
    $this->installEntitySchema('node');
    $this->installSchema('system', 'sequences');
  }

  /**
   * Test creating multiple participant objects referencing the same entity.
   */
  public function testCreateDuplicateEntry() {
    // Create entity to be moderated.
    $node = $this->createNode(['type' => 'entity_test_rev']);

    // Create participant entity that targets entity above.
    $storage = \Drupal::entityTypeManager()->getStorage('workflow_participants');
    $values = [
      'moderated_entity' => [
        'target_id' => $node->id(),
        'target_type' => 'node',
      ],
      'editors' => [],
      'reviewers' => [],
    ];
    $entity = $storage->create($values);
    $storage->save($entity);

    // Expect a storage exception since there is a unique key constraint on the
    // target_id and target_type columns.
    $this->setExpectedException(EntityStorageException::class);
    $duplicate_entity = $storage->create($values);
    $storage->save($duplicate_entity);
  }

}
