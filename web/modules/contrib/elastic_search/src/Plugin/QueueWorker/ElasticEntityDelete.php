<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 31.05.17
 * Time: 10:18
 */

namespace Drupal\elastic_search\Plugin\QueueWorker;

use Drupal\elastic_search\Elastic\ElasticDocumentManager;
use Drupal\elastic_search\ValueObject\QueueItem;
use Psr\Log\LoggerInterface;

/**
 * A Node Publisher that publishes nodes on CRON run.
 * Runs for less time than update, as update is judged to be a more important action
 *
 * @QueueWorker(
 *   id = "elastic_entity_delete",
 *   title = @Translation("Elastic Entity Delete"),
 *   cron = {"time" = 20}
 * )
 */
class ElasticEntityDelete extends ElasticEntityQueueBase {

  /**
   * {@inheritdoc}
   *
   * @var QueueItem[] $data
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\elastic_search\Exception\ElasticDocumentBuilderSkipException
   * @throws \Drupal\elastic_search\Exception\ElasticDocumentManagerRecursionException
   * @throws \Drupal\elastic_search\Exception\IndexNotFoundException
   * @throws \Drupal\elastic_search\Exception\MapNotFoundException
   */
  public function processItem($data) {
    $data = is_array($data) ? $data : [$data];
    $this->process($data, $this->documentManager, $this->logger);
  }

  /**
   * {@inheritdoc}
   */
  public function process(array $entities, ElasticDocumentManager $documentManager, LoggerInterface $logger) {
    //We dont catch any exceptions as we would like it to hard fail, so it remains in the queue and gets logged
    ElasticDocumentManager::deleteOrphanedDocumentsFromQueue($entities, $this->documentManager, $this->logger);
  }

}