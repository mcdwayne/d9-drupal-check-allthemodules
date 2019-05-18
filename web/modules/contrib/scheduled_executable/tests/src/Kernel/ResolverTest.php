<?php

namespace Drupal\Tests\scheduled_executable\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\scheduled_executable\Entity\ScheduledExecutable;

/**
 * Tests that items are correctly resolved before queueing.
 *
 * @group scheduled_executable
 */
class ResolverTest extends KernelTestBase {

  /**
   * A timestamp for the SE's execution.
   *
   * This is earlier than the cron time, so items get executed.
   */
  const TIME_EXECUTION = 1234000500;

  /**
   * A mocked timestamp for the cron run.
   */
  const TIME_CRON = 1234000500 + 100;

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'entity_test',
    'scheduled_executable',
    'scheduled_executable_test_actions',
    'scheduled_executable_test_resolvers',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cron service.
   *
   * @var \Drupal\Core\Cron
   */
  protected $cron;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // These additional tables are necessary because $this->cron->run() calls
    // system_cron().
    $this->installSchema('system', ['key_value_expire']);

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('scheduled_executable');

    $this->cron = \Drupal::service('cron');
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    $this->actionPluginManager = \Drupal::service('plugin.manager.action');
  }

  /**
   * Tests that a resolver can delete an item.
   */
  public function testResolverDelete() {
    // Create two cheduled executables with the same group, to be executed at
    // the same time.
    $action = $this->actionPluginManager->createInstance('scheduled_executable_test_action_name', []);

    // Create a test entity.
    $test_entity_values = [
      'name' => 'keep',
    ];
    $test_entity = $this->entityTypeManager->getStorage('entity_test')->create($test_entity_values);
    $test_entity->save();

    $scheduled_executable = ScheduledExecutable::create()
      ->setExecutablePlugin('action', $action)
      ->setTargetEntity($test_entity)
      ->setGroup('test_group')
      ->setKey('keep')
      ->setExecutionTime(self::TIME_EXECUTION)
      ->setResolver('test_delete');
    $scheduled_executable->save();

    // Create a second test entity.
    // Typically, two actions in the same group would be on the same entity,
    // but we need a way of distinguishing which SE gets executed.
    $test_entity_values = [
      'name' => 'delete',
    ];
    $test_entity = $this->entityTypeManager->getStorage('entity_test')->create($test_entity_values);
    $test_entity->save();

    $scheduled_executable = ScheduledExecutable::create()
      ->setExecutablePlugin('action', $action)
      ->setTargetEntity($test_entity)
      ->setGroup('test_group')
      ->setKey('delete')
      ->setExecutionTime(self::TIME_EXECUTION)
      ->setResolver('test_delete');
    $scheduled_executable->save();

    // Run cron at a time after the execution time.
    $this->container->set('datetime.time', $this->getMock(TimeInterface::class));
    $this->container->get('datetime.time')
      ->method('getRequestTime')
      ->willReturn(self::TIME_CRON);

    $this->cron->run();

    // Note that we can't test that we get added to the queue, as cron queues
    // are run after all hook_cron() implementations, and so will handle the
    // queued item before we can inspect it.

    // Check that the right action has executed.
    $this->assertEquals(['keep'], \Drupal::state()->get('scheduled_executable_test_action_key'), 'The action was executed.');
  }

  // TODO: add a test for re-ordering scheduled items.

}
