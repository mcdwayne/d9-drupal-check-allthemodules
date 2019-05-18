<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 31.05.17
 * Time: 10:18
 */

namespace Drupal\elastic_search\Plugin\QueueWorker;

use Drupal\elastic_search\Elastic\ElasticDocumentManager;
use Psr\Log\LoggerInterface;

/**
 * A Node Publisher that publishes nodes on CRON run.
 *
 * @QueueWorker(
 *   id = "elastic_entity_update",
 *   title = @Translation("Elastic Entity Update"),
 *   cron = {"time" = 30}
 * )
 */
class ElasticEntityUpdate extends ElasticEntityQueueBase {

  /**
   * {@inheritdoc}
   */
  public function process(array $entities, ElasticDocumentManager $documentManager, LoggerInterface $logger) {
    ElasticDocumentManager::updateDocuments($entities, $this->documentManager, $this->logger);
  }

}