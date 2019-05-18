<?php

namespace Drupal\prefetcher\Service;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\prefetcher\PrefetcherCrawlerManager;
use Drush\Commands\DrushCommands;
use Psr\Log\LoggerInterface;

/**
 * Service to prefetch uris.
 */
class Prefetcher {

  /**
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The prefetcher configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The lock backend that should be used.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The prefetcher crawler manager.
   *
   * @var \Drupal\prefetcher\PrefetcherCrawlerManager
   */
  protected $crawlerManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Maximum size of the block.
   *
   * @var int
   */
  protected $maxBlockSize = 100;

  /**
   * Default options for prefetcher run.
   *
   * @var array
   */
  protected $defaultOptions = [
    'block-size' => 0,
    'limit' => 0,
    'not-crawled' => FALSE,
    'expiry' => 0,
    'silent' => TRUE,
  ];

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The prefetcher configuration.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\prefetcher\PrefetcherCrawlerManager $crawler_manager
   *   The prefetcher crawler manager.
   */
  public function __construct(LoggerInterface $logger, ImmutableConfig $config, Connection $database, LockBackendInterface $lock, EntityTypeManagerInterface $entity_type_manager, PrefetcherCrawlerManager $crawler_manager) {
    $this->logger = $logger;
    $this->config = $config;
    $this->database = $database;
    $this->lock = $lock;
    $this->entityTypeManager = $entity_type_manager;
    $this->crawlerManager = $crawler_manager;
  }

  /**
   * Runs the prefetcher to process uris.
   *
   * @param array $options
   *   (optional) Options to control prefetcher processings
   *   - block-size: Block size for each request pool.
   *   - limit: Limit.
   *   - not-crawled: Only process uris which have never been crawled
   *   before.
   *   - expiry: Include uris to prefetch with the given maximum time in
   *   seconds until expiry. When not given, the configuration value will be
   *   used.
   *   - silent: Suppress status messages in the output stream.
   */
  public function run(array $options = []) {
    $options = $options + $this->defaultOptions;
    $silent = $options['silent'];
    if ($this->lock->acquire('prefetcher', 60)) {
      $lock_acquired = TRUE;
      $expiry_date = new \DateTime('now');
      if (!($expiry = $options['expiry'])) {
        $expiry = (int) $this->config->get('expiry');
      }
      if ($expiry > 0) {
        $expiry_date->add(new \DateInterval("PT" . $expiry . "S"));
      }

      $total_start = microtime(TRUE);

      /** @var \Drupal\prefetcher\CrawlerInterface $crawler */
      $crawler = NULL;
      $crawler_settings = $this->config->get('crawler');
      if (!empty($crawler_settings['plugin_id']) && $this->crawlerManager->hasDefinition($crawler_settings['plugin_id'])) {
        $crawler = $this->crawlerManager->createInstance($crawler_settings['plugin_id']);
      }
      else {
        $crawler = $this->crawlerManager->getDefaultCrawler();
      }
      $entity_storage = $this->entityTypeManager->getStorage('prefetcher_uri');
      $query = $entity_storage->getQuery();

      $or = $query->orConditionGroup();
      $or->condition('expires', $expiry_date->format('Y-m-d\TH:i:s'), '<');
      $or->notExists('expires');
      $query->condition($or);
      $query->condition('status', 1);
      $not_crawled = $options['not-crawled'];
      if ($not_crawled) {
        $query->notExists('last_crawled');
      }
      else {
        $query->sort('last_crawled', 'ASC');
      }

      $limit = (int) $options['limit'];
      if (!$limit) {
        if (!$silent) {
          drush_log('Loading total count.', 'ok');
        }
        $count_query = clone $query;
        $limit = $count_query->count()->execute();
      }
      $block_size = (int) $options['block_size'];
      if (!$block_size) {
        $block_size = $limit < $this->maxBlockSize ? $limit : $this->maxBlockSize;
      }

      if (!$silent) {
        drush_log(t("Started crawling process for a total of @total items via @env.", [
          '@total' => $limit,
          '@env' => 'Drush',
        ]), 'ok');
      }
      $this->logger->info(t("Started crawling process for a total of @total items via @env.", [
        '@total' => $limit,
        '@env' => 'Drush',
      ]));

      $i = 0;
      $failed_acquires = 0;
      while ($i < $limit) {
        if ($lock_acquired) {
          $this->lock->release('prefetcher');
        }
        if ($this->lock->acquire('prefetcher', 900)) {
          $lock_acquired = TRUE;
          $start = microtime(TRUE);
          $block_query = clone $query;
          // Since crawling a document will mark it crawled and changing the
          // pager, always process blocks from the start.
          $block_query->range(0, $block_size);

          $entity_ids = $block_query->execute();
          if (!empty($entity_ids)) {
            $entities = $entity_storage->loadMultiple($entity_ids);
            $crawler->crawlMultiple($entities);
          }
          $i += $block_size;
          $time_taken = round(microtime(TRUE) - $start);
          if (!$silent) {
            drush_log(t("Prefetcher processed @count / @limit uri items. Time taken: @time seconds", [
              '@count' => $i,
              '@limit' => $limit,
              '@time' => $time_taken,
            ]), 'ok');
          }
          // Take a break to reduce database workload.
          usleep(200000);
        }
        else {
          if ($failed_acquires > 60) {
            $this->logger->error(t("Failed to continue the prefetcher process via @env, aborting. Maybe another process is running.", ['@env' => 'Drush']));
            return;
          }
          $lock_acquired = FALSE;
          $failed_acquires++;
          sleep(5);
        }
      }

      $total_time = round(microtime(TRUE) - $total_start);
      if (!$silent) {
        drush_log(t("Crawling completed on @total items via @env. Time taken: @time seconds", [
          '@total' => $limit,
          '@env' => 'Drush',
          '@time' => $total_time,
        ]), 'ok');
      }
      $this->logger->info(t("Crawling completed on @total items via @env. Time taken: @time seconds", [
        '@total' => $limit,
        '@env' => 'Drush',
        '@time' => $total_time,
      ]));
      if ($lock_acquired) {
        $this->lock->release('prefetcher');
      }
    }
    else {
      $this->logger->warning(t("@env tried to run a prefetcher while another prefetcher is already running.", ['@env' => 'Drush']));
    }
  }

  /**
   * Resets the prefetcher queue.
   *
   * So that all uri items are being crawled as soon as possible.
   */
  public function reset() {
    $date = new \DateTime('now');
    $this->database->update('prefetcher_uri_field_data')
      ->fields(['expires' => $date->format('Y-m-d\TH:i:s')])->execute();
    drush_log(t("Prefetcher queue has been reset."), 'ok');
    $this->logger->info(t("Prefetcher queue has been reset."));
  }

}
