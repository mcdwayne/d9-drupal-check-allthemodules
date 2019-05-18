<?php

namespace Drupal\Tests\scheduled_executable\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\scheduled_executable\Entity\ScheduledExecutable;

/**
 * Tests that scheduled executable entities are queued and executed.
 *
 * @group scheduled_executable
 */
class ExecutionQueueTest extends KernelTestBase {

  /**
   * A mocked timestamp for the first cron run.
   */
  const TIME_CRON_ONE = 1234000500;

  /**
   * A timestamp for the SE's execution.
   *
   * This is later than the first cron time, but earlier than the second cron
   * time.
   */
  const TIME_EXECUTION = 1234000500 + 100;

  /**
   * A mocked timestamp for the second cron run.
   */
  const TIME_CRON_TWO = 1234000500 + 200;

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'user',
    'entity_test',
    'scheduled_executable_test_actions',
    'scheduled_executable',
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
   * Tests that a scheduled entity with a basic action is queued and executed.
   */
  public function testExecutionQueueBasicAction() {
    // Mock the current time to begin with, so that timestamps set automatically
    // on the entity are consistent.
    // (Note though that the 'changed' timestamp will not get set with the
    // mocked value due to this core bug: https://www.drupal.org/node/2902896.)
    $this->container->set('datetime.time', $this->getMock(TimeInterface::class));
    $this->container->get('datetime.time')
      ->method('getRequestTime')
      ->willReturn(self::TIME_CRON_ONE);

    // Create a test entity.
    $test_entity_values = [
      'name' => $this->randomString(),
    ];
    $test_entity = $this->entityTypeManager->getStorage('entity_test')->create($test_entity_values);
    $test_entity->save();

    // Create a scheduled executable.
    $action = $this->actionPluginManager->createInstance('scheduled_executable_test_action_simple', []);
    $scheduled_executable = ScheduledExecutable::create()
      ->setExecutablePlugin('action', $action)
      ->setTargetEntity($test_entity)
      ->setKey('cake')
      ->setExecutionTime(self::TIME_EXECUTION)
      ->setResolver('default');
    $scheduled_executable->save();

    // Run cron at a time prior to the execution time.
    $this->cron->run();

    // Check that the action has not been executed.
    $this->assertNull(\Drupal::state()->get('scheduled_executable_test_action'), 'The action was not executed.');

    // Run cron at a time after the execution time.
    $this->container->set('datetime.time', $this->getMock(TimeInterface::class));
    $this->container->get('datetime.time')
      ->method('getRequestTime')
      ->willReturn(self::TIME_CRON_TWO);

    // Note that we can't test that we get added to the queue, as cron queues
    // are run after all hook_cron() implementations, and so will handle the
    // queued item before we can inspect it.

    $this->cron->run();

    // Check that the action has executed.
    $this->assertEquals('executed', \Drupal::state()->get('scheduled_executable_test_action'), 'The action was executed.');

    // Check the SE was deleted.
    $result = $this->entityTypeManager->getStorage('scheduled_executable')->load($scheduled_executable->id());
    $this->assertEmpty($result, 'The schedule entity was deleted.');
  }

}
