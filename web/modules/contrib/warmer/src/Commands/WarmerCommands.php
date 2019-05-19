<?php

namespace Drupal\warmer\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\warmer\HookImplementations;
use Drupal\warmer\Plugin\WarmerPluginBase;
use Drupal\warmer\Plugin\WarmerPluginManager;
use Drupal\warmer\QueueManager;
use Drush\Commands\DrushCommands;
use Drush\Drupal\Commands\core\QueueCommands;
use Drush\Utils\StringUtils;

/**
 * Drush commands for the Warmer module.
 */
class WarmerCommands extends DrushCommands {

  private const VERY_HIGH_NUMBER = 99999999;

  /**
   * The warmer manager.
   *
   * @var \Drupal\warmer\Plugin\WarmerPluginManager
   */
  private $warmerManager;

  /**
   * The queue manager.
   *
   * @var \Drupal\warmer\QueueManager
   */
  private $queueManager;

  /**
   * The queue commands.
   *
   * @var \Drush\Drupal\Commands\core\QueueCommands
   */
  private $queueCommands;

  /**
   * WarmerCommands constructor.
   *
   * @param \Drupal\warmer\Plugin\WarmerPluginManager $warmer_manager
   *   The warmer manager.
   * @param \Drupal\warmer\QueueManager $queue_manager
   *   The queue manager.
   * @param \Drush\Drupal\Commands\core\QueueCommands $queue_commands
   *   The service related to queue commands.
   */
  public function __construct(WarmerPluginManager $warmer_manager, QueueManager $queue_manager, QueueCommands $queue_commands) {
    parent::__construct();
    $this->warmerManager = $warmer_manager;
    $this->queueManager = $queue_manager;
    $this->queueCommands = $queue_commands;
  }

  /**
   * Command description here.
   *
   * @param array $warmer_ids
   *   List of plugin IDs separated by comas of the warmer to enqueue. See the
   *   warmer:list command to find all the available warmers.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option run-queue
   *   If supplied, the warmer queue will be flushed at the end. That will
   *   leave all the items in the cache. If not, they'll be processed later by
   *   the queue workers on cron.
   * @usage warmer-enqueue foo,bar --run-queue
   *   Pre-fetches all the items managed by the warmer with IDs foo or bar into
   *   the cache.
   * @usage warmer-enqueue foo
   *   Schedules all the items managed by the warmer with ID foo for
   *   pre-fetching.
   *
   * @command warmer:enqueue
   * @aliases warmer-enqueue
   *
   * @validate-warmer warmer_ids
   *
   * @throws \Exception
   */
  public function enqueue(array $warmer_ids, $options = ['run-queue' => FALSE]) {
    $warmer_ids = array_unique(StringUtils::csvToArray($warmer_ids));
    $warmers = $this->warmerManager->getWarmers($warmer_ids);
    $count_list = array_map(function (WarmerPluginBase $warmer) {
      $count = 0;
      $ids = [NULL];
      while ($ids = $warmer->buildIdsBatch(end($ids))) {
        $this->queueManager->enqueueBatch(HookImplementations::class . '::warmBatch', $ids, $warmer);
        $count += count($ids);
      }
      return $count;
    }, $warmers);
    $total = array_sum($count_list);
    $this->logger()->success(
      dt('@total items enqueued for cache warming.', ['@total' => $total])
    );
    if (!$options['run-queue']) {
      $this->logger()->notice(
        dt('If you need your items into cache right away you can run "drush queue-run warmer".')
      );
      return;
    }
    $this->logger()->success(dt('Warming caches in batches from the "warmer" queue.', ['@count' => $count]));
    $this->queueCommands->run('warmer', ['time-limit' => static::VERY_HIGH_NUMBER]);
    return;
  }

  /**
   * An example of the table output format.
   *
   * @param array $options An associative array of options whose values come from cli, aliases, config, etc.
   *
   * @field-labels
   *   id: ID
   *   label: Label
   *   description: Description
   *   frequency: Frequency
   *   batchSize: Batch Size
   * @default-fields id,label,description,frequency,batchSize
   *
   * @command warmer:list
   * @aliases warmer-list
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   */
  public function list($options = ['format' => 'table']) {
    $rows = array_map(function (WarmerPluginBase $warmer) {
      $definition = $warmer->getPluginDefinition();
      return [
        'id' => $warmer->getPluginId(),
        'label' => $definition['label'],
        'description' => $definition['description'],
        'frequency' => $warmer->getFrequency(),
        'batchSize' => $warmer->getBatchSize(),
      ];
    }, $this->warmerManager->getWarmers());
    return new RowsOfFields($rows);
  }

  /**
   * Validate that queue permission exists.
   *
   * Annotation value should be the name of the argument/option containing the name.
   *
   * @hook validate @validate-warmer
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   * @return \Consolidation\AnnotatedCommand\CommandError|NULL
   */
  public function validateWarmerNames(CommandData $commandData) {
    $arg_name = $commandData->annotationData()->get('validate-warmer', null);
    $warmer_ids = $commandData->input()->getArgument($arg_name);
    $warmer_ids = StringUtils::csvToArray($warmer_ids);
    $definitions = $this->warmerManager->getDefinitions();
    $actual_warmer_ids = array_keys($definitions);
    $missing = array_diff($warmer_ids, $actual_warmer_ids);
    if (!empty($missing)) {
      $message = dt('Warmer plugin(s) not found: !names.', ['!names' => implode(', ', $missing)]);
      return new CommandError($message);
    }
    return NULL;
  }

}
