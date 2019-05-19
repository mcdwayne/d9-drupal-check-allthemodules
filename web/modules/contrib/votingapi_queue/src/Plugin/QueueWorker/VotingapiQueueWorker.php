<?php

namespace Drupal\votingapi_queue\Plugin\QueueWorker;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * The VotingAPI implementation class.
 *
 * @QueueWorker(
 *   id = "votingapi_queue",
 *   title = @Translation("VotingAPI queue")
 * )
 */
class VotingapiQueueWorker extends QueueWorkerBase {

  const DEFAULT_ADD_LIMIT = 1000;

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /*
     * @var VoteResultFunctionManager $manager
     */
    $manager = \Drupal::service('plugin.manager.votingapi.resultfunction');
    $manager->recalculateResults(
      $data['entity_type_id'],
      $data['entity_id'],
      $data['vote_type']
    );
    $cache_tag = $data['entity_type'] . ':' . $data['entity_id'];
    Cache::invalidateTags([$cache_tag]);
  }

}
