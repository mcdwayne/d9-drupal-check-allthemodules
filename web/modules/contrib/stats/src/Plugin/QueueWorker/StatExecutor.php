<?php
/**
 * @file
 * StatExecutor.php for kartslalom
 */

namespace Drupal\stats\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Executes interface translation queue tasks.
 *
 * @QueueWorker(
 *   id = "stat_executor",
 *   title = @Translation("Stat executor"),
 * )
 */
class StatExecutor extends QueueWorkerBase {

  /**
   * @inheritdoc
   */
  public function processItem($data) {
    /** @var \Drupal\stats\StatsExecutor $stats_executor */
    $stats_executor = \Drupal::service('stats.executor');

    $entity_type = $data['entity_type'];
    $entity_id = $data['entity_id'];
    $stat_processor = $data['value'];
    $stats_executor->executeByIds($entity_type, $entity_id, $stat_processor);
  }

}
