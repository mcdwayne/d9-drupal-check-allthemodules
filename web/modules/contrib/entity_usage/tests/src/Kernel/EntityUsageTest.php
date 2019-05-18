<?php

namespace Drupal\Tests\entity_usage\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_usage\Events\EntityUsageEvent;
use Drupal\entity_usage\Events\Events;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the basic API operations of our tracking service..
 *
 * @group entity_usage
 *
 * @package Drupal\Tests\entity_usage\Kernel
 */
class EntityUsageTest extends EntityKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['entity_usage'];

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * Some test entities.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $testEntities;

  /**
   * The injected database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $injectedDatabase;

  /**
   * The name of the table that stores entity usage information.
   *
   * @var string
   */
  protected $tableName;

  /**
   * State service for recording information received by event listeners.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->injectedDatabase = $this->container->get('database');

    $this->installSchema('entity_usage', ['entity_usage']);
    $this->tableName = 'entity_usage';

    // Create two test entities.
    $this->testEntities = $this->getTestEntities();

    $this->state = \Drupal::state();
    \Drupal::service('event_dispatcher')->addListener(Events::USAGE_ADD,
      [$this, 'usageAddEventRecorder']);
    \Drupal::service('event_dispatcher')->addListener(Events::USAGE_DELETE,
      [$this, 'usageDeleteEventRecorder']);
    \Drupal::service('event_dispatcher')->addListener(Events::BULK_TARGETS_DELETE,
      [$this, 'usageBulkTargetDeleteEventRecorder']);
    \Drupal::service('event_dispatcher')->addListener(Events::BULK_HOSTS_DELETE,
      [$this, 'usageBulkHostsDeleteEventRecorder']);
  }

  /**
   * Tests the listUsage() method.
   *
   * @covers \Drupal\entity_usage\EntityUsage::listUsage
   * @covers \Drupal\entity_usage\EntityUsage::listReferencedEntities
   */
  public function testGetUsage() {
    $target_entity = $this->testEntities[0];
    $referencing_entity = $this->testEntities[1];
    $this->injectedDatabase->insert($this->tableName)
      ->fields([
        't_id' => $target_entity->id(),
        't_type' => $target_entity->getEntityTypeId(),
        're_id' => $referencing_entity->id(),
        're_type' => $referencing_entity->getEntityTypeId(),
        'method' => 'entity_reference',
        'count' => 1,
      ])
      ->execute();

    /** @var \Drupal\entity_usage\EntityUsage $entity_usage */
    $entity_usage = $this->container->get('entity_usage.usage');
    $complete_usage = $entity_usage->listUsage($target_entity);
    $usage = $complete_usage[$referencing_entity->getEntityTypeId()][$referencing_entity->id()];
    $this->assertEquals(1, $usage, 'Returned the correct count, without tracking method.');

    $complete_usage = $entity_usage->listUsage($target_entity, TRUE);
    $usage = $complete_usage['entity_reference'][$referencing_entity->getEntityTypeId()][$referencing_entity->id()];
    $this->assertEquals(1, $usage, 'Returned the correct count, with tracking method.');

    $complete_references_entities = $entity_usage->listReferencedEntities($referencing_entity);
    $usage = $complete_references_entities[$target_entity->getEntityTypeId()][$target_entity->id()];
    $this->assertEquals(1, $usage, 'Returned the correct count.');

    // Clean back the environment.
    $this->injectedDatabase->truncate($this->tableName);
  }

  /**
   * Tests the add() method.
   *
   * @covers \Drupal\entity_usage\EntityUsage::add
   */
  public function testAddUsage() {
    $entity = $this->testEntities[0];
    /** @var \Drupal\entity_usage\EntityUsage $entity_usage */
    $entity_usage = $this->container->get('entity_usage.usage');
    $entity_usage->add($entity->id(), $entity->getEntityTypeId(), '1', 'foo', 'entity_reference', 1);

    $event = \Drupal::state()->get('entity_usage_events_test.usage_add', []);

    $this->assertSame($event['event_name'], Events::USAGE_ADD);
    $this->assertSame($event['target_id'], $entity->id());
    $this->assertSame($event['target_type'], $entity->getEntityTypeId());
    $this->assertSame($event['referencing_id'], '1');
    $this->assertSame($event['referencing_type'], 'foo');
    $this->assertSame($event['method'], 'entity_reference');
    $this->assertSame($event['count'], 1);

    $real_usage = $this->injectedDatabase->select($this->tableName, 'e')
      ->fields('e', ['count'])
      ->condition('e.t_id', $entity->id())
      ->execute()
      ->fetchField();

    $this->assertEquals(1, $real_usage, 'Usage saved correctly to the database.');

    // Clean back the environment.
    $this->injectedDatabase->truncate($this->tableName);

  }

  /**
   * Tests the delete() method.
   *
   * @covers \Drupal\entity_usage\EntityUsage::delete
   */
  public function testRemoveUsage() {
    $entity = $this->testEntities[0];
    /** @var \Drupal\entity_usage\EntityUsage $entity_usage */
    $entity_usage = $this->container->get('entity_usage.usage');

    $this->injectedDatabase->insert($this->tableName)
      ->fields([
        't_id' => $entity->id(),
        't_type' => $entity->getEntityTypeId(),
        're_id' => 1,
        're_type' => 'foo',
        'method' => 'entity_reference',
        'count' => 3,
      ])
      ->execute();

    // Normal decrement.
    $entity_usage->delete($entity->id(), $entity->getEntityTypeId(), 1, 'foo', 1);

    $event = \Drupal::state()->get('entity_usage_events_test.usage_delete', []);

    $this->assertSame($event['event_name'], Events::USAGE_DELETE);
    $this->assertSame($event['target_id'], $entity->id());
    $this->assertSame($event['target_type'], $entity->getEntityTypeId());
    $this->assertSame($event['referencing_id'], 1);
    $this->assertSame($event['referencing_type'], 'foo');
    $this->assertSame($event['method'], NULL);
    $this->assertSame($event['count'], 1);

    $count = $this->injectedDatabase->select($this->tableName, 'e')
      ->fields('e', ['count'])
      ->condition('e.t_id', $entity->id())
      ->condition('e.t_type', $entity->getEntityTypeId())
      ->execute()
      ->fetchField();
    $this->assertEquals(2, $count, 'The count was decremented correctly.');

    // Multiple decrement and removal.
    $entity_usage->delete($entity->id(), $entity->getEntityTypeId(), 1, 'foo', 2);
    $count = $this->injectedDatabase->select($this->tableName, 'e')
      ->fields('e', ['count'])
      ->condition('e.t_id', $entity->id())
      ->condition('e.t_type', $entity->getEntityTypeId())
      ->execute()
      ->fetchField();
    $this->assertSame(FALSE, $count, 'The count was removed entirely when empty.');

    // Non-existent decrement.
    $entity_usage->delete($entity->id(), $entity->getEntityTypeId(), 1, 'foo', 2);
    $count = $this->injectedDatabase->select($this->tableName, 'e')
      ->fields('e', ['count'])
      ->condition('e.t_id', $entity->id())
      ->condition('e.t_type', $entity->getEntityTypeId())
      ->execute()
      ->fetchField();
    $this->assertSame(FALSE, $count, 'Decrementing non-existing record complete.');

    // Clean back the environment.
    $this->injectedDatabase->truncate($this->tableName);
  }

  /**
   * Tests the bulkDeleteTargets() method.
   *
   * @covers \Drupal\entity_usage\EntityUsage::bulkDeleteTargets
   */
  public function testBulkDeleteTargets() {

    $entity_type = $this->testEntities[0]->getEntityTypeId();

    // Create 2 fake registers on the database table, one for each entity.
    foreach ($this->testEntities as $entity) {
      $this->injectedDatabase->insert($this->tableName)
        ->fields([
          't_id' => $entity->id(),
          't_type' => $entity_type,
          're_id' => 1,
          're_type' => 'foo',
          'method' => 'entity_reference',
          'count' => 1,
        ])
        ->execute();
    }

    /** @var \Drupal\entity_usage\EntityUsage $entity_usage */
    $entity_usage = $this->container->get('entity_usage.usage');
    $entity_usage->bulkDeleteTargets($entity_type);

    $event = \Drupal::state()->get('entity_usage_events_test.usage_bulk_target_delete', []);

    $this->assertSame($event['event_name'], Events::BULK_TARGETS_DELETE);
    $this->assertSame($event['target_id'], NULL);
    $this->assertSame($event['target_type'], $entity_type);
    $this->assertSame($event['referencing_id'], NULL);
    $this->assertSame($event['referencing_type'], NULL);
    $this->assertSame($event['method'], NULL);
    $this->assertSame($event['count'], NULL);

    // Check if there are no records left.
    $count = $this->injectedDatabase->select($this->tableName, 'e')
      ->fields('e', ['count'])
      ->condition('e.t_type', $entity_type)
      ->execute()
      ->fetchField();
    $this->assertSame(FALSE, $count, 'Successfully deleted all records of this type.');

    // Clean back the environment.
    $this->injectedDatabase->truncate($this->tableName);
  }

  /**
   * Tests the bulkDeleteHosts() method.
   *
   * @covers \Drupal\entity_usage\EntityUsage::bulkDeleteHosts
   */
  public function testBulkDeleteHosts() {

    $entity_type = $this->testEntities[0]->getEntityTypeId();

    // Create 2 fake registers on the database table, one for each entity.
    foreach ($this->testEntities as $entity) {
      $this->injectedDatabase->insert($this->tableName)
        ->fields([
          't_id' => 1,
          't_type' => 'foo',
          're_id' => $entity->id(),
          're_type' => $entity_type,
          'method' => 'entity_reference',
          'count' => 1,
        ])
        ->execute();
    }

    /** @var \Drupal\entity_usage\EntityUsage $entity_usage */
    $entity_usage = $this->container->get('entity_usage.usage');
    $entity_usage->bulkDeleteHosts($entity_type);

    $event = \Drupal::state()->get('entity_usage_events_test.usage_bulk_hosts_delete', []);

    $this->assertSame($event['event_name'], Events::BULK_HOSTS_DELETE);
    $this->assertSame($event['target_id'], NULL);
    $this->assertSame($event['target_type'], NULL);
    $this->assertSame($event['referencing_id'], NULL);
    $this->assertSame($event['referencing_type'], $entity_type);
    $this->assertSame($event['method'], NULL);
    $this->assertSame($event['count'], NULL);

    // Check if there are no records left.
    $count = $this->injectedDatabase->select($this->tableName, 'e')
      ->fields('e', ['count'])
      ->condition('e.re_type', $entity_type)
      ->execute()
      ->fetchField();
    $this->assertSame(FALSE, $count, 'Successfully deleted all records of this type.');

    // Clean back the environment.
    $this->injectedDatabase->truncate($this->tableName);
  }

  /**
   * Creates two test entities.
   *
   * @return array
   *   An array of entity objects.
   */
  protected function getTestEntities() {

    $content_entity_1 = EntityTest::create(['name' => $this->randomMachineName()]);
    $content_entity_1->save();
    $content_entity_2 = EntityTest::create(['name' => $this->randomMachineName()]);
    $content_entity_2->save();

    return [
      $content_entity_1,
      $content_entity_2,
    ];
  }

  /**
   * Reacts to save event.
   *
   * @param \Drupal\entity_usage\Events\EntityUsageEvent $event
   *    The entity usage event.
   * @param string $name
   *    The name of the event.
   */
  public function usageAddEventRecorder(EntityUsageEvent $event, $name) {
    $this->state->set('entity_usage_events_test.usage_add', [
      'event_name' => $name,
      'target_id' => $event->getTargetEntityId(),
      'target_type' => $event->getTargetEntityType(),
      'referencing_id' => $event->getReferencingEntityId(),
      'referencing_type' => $event->getReferencingEntityType(),
      'method' => $event->getReferencingMethod(),
      'count' => $event->getCount(),
    ]);
  }

  /**
   * Reacts to delete event.
   *
   * @param \Drupal\entity_usage\Events\EntityUsageEvent $event
   *    The entity usage event.
   * @param string $name
   *    The name of the event.
   */
  public function usageDeleteEventRecorder(EntityUsageEvent $event, $name) {
    $this->state->set('entity_usage_events_test.usage_delete', [
      'event_name' => $name,
      'target_id' => $event->getTargetEntityId(),
      'target_type' => $event->getTargetEntityType(),
      'referencing_id' => $event->getReferencingEntityId(),
      'referencing_type' => $event->getReferencingEntityType(),
      'method' => $event->getReferencingMethod(),
      'count' => $event->getCount(),
    ]);
  }

  /**
   * Reacts to bulk target delete event.
   *
   * @param \Drupal\entity_usage\Events\EntityUsageEvent $event
   *    The entity usage event.
   * @param string $name
   *    The name of the event.
   */
  public function usageBulkTargetDeleteEventRecorder(EntityUsageEvent $event, $name) {
    $this->state->set('entity_usage_events_test.usage_bulk_target_delete', [
      'event_name' => $name,
      'target_id' => $event->getTargetEntityId(),
      'target_type' => $event->getTargetEntityType(),
      'referencing_id' => $event->getReferencingEntityId(),
      'referencing_type' => $event->getReferencingEntityType(),
      'method' => $event->getReferencingMethod(),
      'count' => $event->getCount(),
    ]);
  }

  /**
   * Reacts to bulk hosts delete event.
   *
   * @param \Drupal\entity_usage\Events\EntityUsageEvent $event
   *    The entity usage event.
   * @param string $name
   *    The name of the event.
   */
  public function usageBulkHostsDeleteEventRecorder(EntityUsageEvent $event, $name) {
    $this->state->set('entity_usage_events_test.usage_bulk_hosts_delete', [
      'event_name' => $name,
      'target_id' => $event->getTargetEntityId(),
      'target_type' => $event->getTargetEntityType(),
      'referencing_id' => $event->getReferencingEntityId(),
      'referencing_type' => $event->getReferencingEntityType(),
      'method' => $event->getReferencingMethod(),
      'count' => $event->getCount(),
    ]);
  }

}
