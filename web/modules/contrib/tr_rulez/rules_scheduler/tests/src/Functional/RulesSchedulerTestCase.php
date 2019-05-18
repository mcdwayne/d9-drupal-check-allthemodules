<?php

namespace Drupal\Tests\rules_scheduler\Functional;

use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\Tests\rules\Functional\RulesBrowserTestBase;
use Drupal\rules_scheduler\Entity\Task;

/**
 * Test cases for the Rules Scheduler module.
 *
 * @group Rules
 */
class RulesSchedulerTestCase extends RulesBrowserTestBase {
  use CronRunTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'rules',
    'rules_scheduler',
    'rules_scheduler_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->logger = $this->container->get('logger.channel.rules');
    $this->logger->clearLogs();

    $rulesSettings = \Drupal::configFactory()->getEditable('rules.settings');
    $rulesSettings->set('log', TRUE)->save();
  }

  /**
   * Tests that custom task handlers are properly invoked.
   */
  public function testCustomTaskHandler() {
    // Set up a scheduled task that will simply write a variable when executed.
    $variable = 'rules_scheduler_task_handler_variable';
    $task = Task::create([
      'date' => REQUEST_TIME,
      'identifier' => 'rules_scheduler_test',
      'config' => '',
      'data' => ['variable' => $variable],
      'handler' => 'Drupal\rules_scheduler_test\TestTaskHandler',
    ]);
    $task->schedule();

    // Run cron. In hook_cron(), Rules Scheduler will queue the task, then cron
    // will use the queue API to run the workers to process the task.
    $this->cronRun();

    // The test task handler simply sets the passed variable to TRUE,
    // so test to see that the test task handler actually ran and worked.
    $testSettings = \Drupal::configFactory()->get('rules_scheduler_test.settings');
    $this->assertTrue($testSettings->get($variable));
  }

}
