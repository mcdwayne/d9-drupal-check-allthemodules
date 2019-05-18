<?php

namespace Drupal\Tests\multiversion\Functional;

/**
 * Test the hooks invoking the UuidIndex class.
 *
 * @group multiversion
 */
class UuidIndexHooksTest extends MultiversionFunctionalTestBase {

  public function testEntityHooks() {
    $keys = $this->uuidIndex->get('foo');
    $this->assertTrue(empty($keys), 'Empty array was returned when fetching non-existing UUID.');

    /** @var \Drupal\Core\Entity\EntityStorageInterface $entity_test_storage */
    $entity_test_storage = $this->container->get('entity.manager')->getStorage('entity_test');
    $entity = $entity_test_storage->create();
    $entity->save();
    $keys = $this->uuidIndex->get($entity->uuid());
    $this->assertEqual(
      [
        'entity_type_id' => $entity->getEntityTypeId(),
        'entity_id' => $entity->id(),
        'revision_id' => $entity->getRevisionId(),
        'rev' => $entity->_rev->value,
        'is_stub' => $entity->_rev->is_stub,
        'uuid' => $entity->uuid(),
        'status' => 'available',
      ],
      $keys,
      'Index entry was created by insert hook.'
    );

    $entities = $entity_test_storage->loadMultiple([$entity->id()]);
    $entity_test_storage->delete($entities);
    $keys = $this->uuidIndex->get($entity->uuid());
    $this->assertTrue(!empty($keys), 'Index entry should not be removed when an entity is deleted.');
  }

}
