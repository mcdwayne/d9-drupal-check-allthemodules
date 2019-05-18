<?php

namespace Drupal\openimmo;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Default implementation of OpenImmoManagerInterface.
 */
class OpenImmoManager implements OpenImmoManagerInterface {
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $openimmoSettings;

  /**
   * Drupal\openimmo\OpenImmoProcessorInterface definition.
   *
   * @var \Drupal\openimmo\OpenImmoProcessorInterface
   */
  protected $openimmoProcessor;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * An array of available queries.
   *
   * @var array
   */
  protected $queries;

  /**
   * Constructs a OpenImmoManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\openimmo\OpenImmoProcessorInterface $openimmo_processor
   *   The OpenImmo Processor service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, OpenImmoProcessorInterface $openimmo_processor, EntityTypeManagerInterface $entity_type_manager) {
    $this->openimmoSettings = $config_factory->get('openimmo.settings');
    $this->openimmoProcessor = $openimmo_processor;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshOpenImmoData() {

    $sources = $this->getSources();

    foreach ($sources as $source) {
      $this->openimmoProcessor->createFetchTask($source);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSources() {

    $sources = [];

    $storage = $this->entityTypeManager->getStorage('openimmo');
    $query = $storage->getQuery();
    $qids = $query->execute();

    $this->queries = $this->entityTypeManager->getStorage('openimmo')->loadMultiple($qids);

    foreach ($this->queries as $source) {
      $sources[] = $source->toArray();
    }

    return $sources;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchDataBatch(array &$context) {
    if (empty($context['sandbox']['max'])) {
      $context['finished'] = 0;
      $context['sandbox']['max'] = $this->openimmoProcessor->numberOfQueueItems();
      $context['sandbox']['progress'] = 0;
      $context['message'] = $this->t('Loading openimmo data ...');
      $context['results']['updated'] = 0;
      $context['results']['failures'] = 0;
      $context['results']['processed'] = 0;
    }

    // Grab another item from the fetch queue.
    for ($i = 0; $i < 5; $i++) {
      if ($item = $this->openimmoProcessor->claimQueueItem()) {
        if ($this->openimmoProcessor->processFetchTask($item->data)) {
          $context['results']['updated']++;
          $context['message'] = $this->t('Loaded openimmo data for %title.', ['%title' => $item->data['info']['name']]);
        }
        else {
          $context['message'] = $this->t('Failed to load openimmo data for %title.', ['%title' => $item->data['info']['name']]);
          $context['results']['failures']++;
        }
        $context['sandbox']['progress']++;
        $context['results']['processed']++;
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
        $this->openimmoProcessor->deleteQueueItem($item);
      }
      else {
        $context['finished'] = 1;
        return;
      }
    }
  }

}
