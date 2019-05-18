<?php

namespace Drupal\Tests\multiversion\Functional;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Test the methods on the UuidIndex class.
 *
 * @group multiversion
 */
class UuidIndexTest extends MultiversionFunctionalTestBase {

  public function testMethods() {
    $entity = EntityTest::create();
    $uuid = $entity->uuid();

    $this->uuidIndex->add($entity);
    $entry = $this->uuidIndex->get($uuid);
    $expected = [
      'entity_type_id' => 'entity_test',
      'entity_id' => 0,
      'revision_id' => 0,
      'uuid' => $uuid,
      'rev' => $entity->_rev->value,
      'is_stub' => $entity->_rev->is_stub,
      'status' => 'indexed',
    ];
    $this->assertEqual($expected, $entry, 'Single entry is correct for an entity that was not yet saved.');

    $entity->save();
    $this->uuidIndex->add($entity);
    $entry = $this->uuidIndex->get($uuid);
    $expected = [
      'entity_type_id' => 'entity_test',
      'entity_id' => 1,
      'revision_id' => 1,
      'uuid' => $uuid,
      'rev' => $entity->_rev->value,
      'is_stub' => $entity->_rev->is_stub,
      'status' => 'available',
    ];
    $this->assertEqual($expected, $entry, 'Single entry is correct for an entity that was saved.');

    $entities = [];
    $uuid = [];
    $rev = [];
    $is_stub = [];

    $entity = $entities[] = EntityTest::create();
    $uuid[] = $entity->uuid();
    $rev[] = $entity->_rev->value;
    $is_stub[] = $entity->_rev->is_stub;

    $entity = $entities[] = EntityTest::create();
    $uuid[] = $entity->uuid();
    $rev[] = $entity->_rev->value;
    $is_stub[] = $entity->_rev->is_stub;

    $this->uuidIndex->addMultiple($entities);
    $expected = [
      $uuid[0] => [
        'entity_type_id' => 'entity_test',
        'entity_id' => 0,
        'revision_id' => 0,
        'rev' => $rev[0],
        'is_stub' => $is_stub[0],
        'uuid' => $uuid[0],
        'status' => 'indexed',
      ],
      $uuid[1] => [
        'entity_type_id' => 'entity_test',
        'entity_id' => 0,
        'revision_id' => 0,
        'rev' => $rev[1],
        'is_stub' => $is_stub[1],
        'uuid' => $uuid[1],
        'status' => 'indexed',
      ],
    ];
    $entries = $this->uuidIndex->getMultiple([$uuid[0], $uuid[1]]);
    $this->assertEqual($expected, $entries, 'Multiple entries are correct.');

    /** @var \Drupal\Core\Entity\EntityStorageInterface $workspace_storage */
    $workspace_storage = $this->container->get('entity.manager')->getStorage('workspace');
    // Create new workspaces and query those.
    $ws1 = $this->randomMachineName();
    $workspace_storage->create(['machine_name' => $ws1, 'type' => 'basic']);
    $ws2 = $this->randomMachineName();
    $workspace_storage->create(['machine_name' => $ws2, 'type' => 'basic']);

    $entity = EntityTest::create();
    $uuid = $entity->uuid();
    $rev = $entity->_rev->value;
    $is_stub = $entity->_rev->is_stub;

    $this->uuidIndex->useWorkspace($ws1)->add($entity);
    $entry = $this->uuidIndex
      ->useWorkspace($ws2)
      ->get($uuid);
    $this->assertTrue(empty($entry), 'New workspace is empty');

    $this->uuidIndex
      ->useWorkspace($ws2)
      ->add($entity);

    $entry = $this->uuidIndex
      ->useWorkspace($ws2)
      ->get($uuid);

    $expected = [
      'entity_type_id' => 'entity_test',
      'entity_id' => 0,
      'revision_id' => 0,
      'rev' => $rev,
      'is_stub' => $is_stub,
      'uuid' => $uuid,
      'status' => 'indexed',
    ];
    $this->assertEqual($expected, $entry, 'Entry was added and fetched from new workspace.');
  }

}
