<?php

namespace Drupal\Tests\cached_computed_field\Kernel;

use Drupal\KernelTests\KernelTestBase as DrupalKernelTestBase;

/**
 * Base class containing common methods for running kernel tests.
 */
abstract class KernelTestBase extends DrupalKernelTestBase {

  /**
   * The name of the test cached computed field.
   */
  const TEST_FIELD = 'cached_field_integer';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'cached_computed_field',
    'cached_computed_field_test',
    'entity_test',
    'field',
    'system',
    'user',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity_test entity type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The queue that contains expired fields.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The entities used in this test.
   *
   * @var \Drupal\entity_test\Entity\EntityTest[]
   */
  protected $entities;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig([
      'cached_computed_field',
      'cached_computed_field_test',
    ]);
    $this->installEntitySchema('entity_test');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->entityStorage = $this->entityTypeManager->getStorage('entity_test');
    $this->queue = $this->container->get('queue')->get('cached_computed_field_expired_fields', FALSE);
  }

  /**
   * Creates a number of test entities.
   *
   * @param int $count
   *   The number of entities to create.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown if the entity storage for the `entity_test` entity doesn't exist.
   */
  protected function createTestEntities($count) {
    for ($i = 0; $i < $count; $i++) {
      $entity = $this->entityStorage->create([
        'title' => $this->randomMachineName(),
        'type' => 'entity_test',
      ]);
      $entity->save();
      $this->entities[] = $entity;
    }
  }

  /**
   * Sets the batch size to use in the test.
   *
   * @param int $batch_size
   *   The batch size.
   */
  protected function setBatchSize($batch_size) {
    $this->config('cached_computed_field.settings')->set('batch_size', $batch_size)->save();
  }

  /**
   * Sets the time limit for processing items that will be used in the test.
   *
   * @param int $time_limit
   *   The time limit, in seconds.
   */
  protected function setTimeLimit($time_limit) {
    $this->config('cached_computed_field.settings')->set('time_limit', $time_limit)->save();
  }

  /**
   * Simulates a cron run.
   *
   * @throws \Exception
   *   An exception may be thrown while processing an item in the queue.
   *
   * @see \Drupal\Core\Cron::run()
   */
  protected function simulateCronRun() {
    cached_computed_field_cron();
  }


}
