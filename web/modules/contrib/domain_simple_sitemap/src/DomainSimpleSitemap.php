<?php

namespace Drupal\domain_simple_sitemap;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathValidator;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\simple_sitemap\Batch;
use Drupal\simple_sitemap\EntityHelper;
use Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager;
use Drupal\simple_sitemap\Simplesitemap;
use Drupal\simple_sitemap\SitemapGenerator;

/**
 * Class DomainSimpleSitemap.
 *
 * @package Drupal\domain_simple_sitemap
 */
class DomainSimpleSitemap extends Simplesitemap {

  /**
   * The domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * DomainSimpleSitemap constructor.
   *
   * @param \Drupal\simple_sitemap\SitemapGenerator $sitemapGenerator
   *   The sitemap generator.
   * @param \Drupal\simple_sitemap\EntityHelper $entityHelper
   *   The entity helper.
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Path\PathValidator $pathValidator
   *   The path validator.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   The date formatter.
   * @param \Drupal\Component\Datetime\Time $time
   *   The time service.
   * @param \Drupal\simple_sitemap\Batch $batch
   *   The batch service.
   * @param \Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator\UrlGeneratorManager $urlGeneratorManager
   *   The url generator manager.
   * @param \Drupal\domain\DomainNegotiatorInterface $domain_negotiator
   *   The domain negotiator.
   */
  public function __construct(
    SitemapGenerator $sitemapGenerator,
    EntityHelper $entityHelper,
    ConfigFactory $configFactory,
    Connection $database,
    EntityTypeManagerInterface $entityTypeManager,
    PathValidator $pathValidator,
    DateFormatter $dateFormatter,
    Time $time,
    Batch $batch,
    UrlGeneratorManager $urlGeneratorManager,
    DomainNegotiatorInterface $domain_negotiator
  ) {
    parent::__construct(
      $sitemapGenerator,
      $entityHelper,
      $configFactory,
      $database,
      $entityTypeManager,
      $pathValidator,
      $dateFormatter,
      $time,
      $batch,
      $urlGeneratorManager
    );
    $this->domainNegotiator = $domain_negotiator;
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchSitemapChunkInfo() {
    return $this->db
      ->query('SELECT id, sitemap_created, domain_id FROM {simple_sitemap} where domain_id = :domain_id', ['domain_id' => $this->domainNegotiator->getActiveId()])
      ->fetchAllAssoc('id');
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchSitemapChunk($id) {
    return $this->db->query('SELECT * FROM {simple_sitemap} WHERE id = :id AND domain_id = :domain_id',
      [':id' => $id, 'domain_id' => $this->domainNegotiator->getActiveId()])->fetchObject();
  }

  /**
   * Generates the sitemap for all languages and saves it to the db.
   *
   * @param string $from
   *   Can be 'form', 'cron', 'drush' or 'nobatch'.
   *   This decides how the batch process is to be run.
   */
  public function generateSitemap($from = 'form') {

    $this->batch->setBatchSettings([

      'batch_process_limit' => $this->getSetting('batch_process_limit', NULL),
      'max_links' => $this->getSetting('max_links', 2000),
      'skip_untranslated' => $this->getSetting('skip_untranslated', FALSE),
      'remove_duplicates' => $this->getSetting('remove_duplicates', TRUE),
      'excluded_languages' => $this->getSetting('excluded_languages', []),
      'from' => $from,
    ]);

    $plugins = $this->urlGeneratorManager->getDefinitions();

    usort($plugins, function ($a, $b) {
      return $a['weight'] - $b['weight'];
    });

    // For each chunk/domain generate custom URLs and entities.
    $domains = $this->entityTypeManager->getStorage('domain')->loadMultiple();
    foreach ($domains as $domain) {
      if ($domain->status()) {
        foreach ($plugins as $plugin) {
          if ($plugin['enabled']) {
            if ($plugin['settings']['instantiate_for_each_data_set']) {
              foreach ($this->urlGeneratorManager->createInstance($plugin['id'])->getDataSets() as $data_sets) {
                $this->batch->addDomainOperation($plugin['id'], $domain, $data_sets);
              }
            }
            else {
              $this->batch->addDomainOperation($plugin['id'], $domain);
            }
          }
        }
      }
    }

    $success = $this->batch->start();
    return $from === 'nobatch' ? $this : $success;
  }

  /**
   * {@inheritdoc}
   */
  public function getSitemap($chunk_id = NULL) {
    $chunk_info = $this->fetchSitemapChunkInfo();

    if (NULL === $chunk_id || !isset($chunk_info[$chunk_id])) {

      if (count($chunk_info) > 1) {
        // Return sitemap index, if there are multiple sitemap chunks.
        return $this->getSitemapIndex($chunk_info);
      }
      else {
        // Return sitemap if there is only one chunk.
        if (count($chunk_info) === 1) {
          $id = key($chunk_info);
          return $this->fetchSitemapChunk($id)->sitemap_string;
        } else {
          return FALSE;
        }
      }
    }
    else {
      // Return specific sitemap chunk.
      return $this->fetchSitemapChunk($chunk_id)->sitemap_string;
    }
  }

}
