<?php

namespace Drupal\real_estate_rets;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Default implementation of RetsManagerInterface.
 */
class RetsManager implements RetsManagerInterface {
  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $retsSettings;

  /**
   * Drupal\real_estate_rets\RetsProcessorInterface definition.
   *
   * @var \Drupal\real_estate_rets\RetsProcessorInterface
   */
  protected $retsProcessor;

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
   * Constructs a RetsManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\real_estate_rets\RetsProcessorInterface $rets_processor
   *   The Rets Processor service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RetsProcessorInterface $rets_processor, EntityTypeManagerInterface $entity_type_manager) {
    $this->retsSettings = $config_factory->get('rets.settings');
    $this->retsProcessor = $rets_processor;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshRetsData() {

    $query_sets = $this->getQueries();

    foreach ($query_sets as $query_set) {
      $connection = array_slice($query_set, 0, -1);
      foreach ($query_set['queries'] as $key => $query) {
        $query = array_merge($connection, ['query_id' => $key], $query);
        $this->retsProcessor->createFetchTask($query);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getQueries() {

    $connects = [];

    $storage = $this->entityTypeManager->getStorage('real_estate_rets_connection');
    $query = $storage->getQuery();

    $qids = $query->execute();

    $this->queries = $this->entityTypeManager->getStorage('real_estate_rets_connection')->loadMultiple($qids);

    foreach ($this->queries as $connect) {
      $connects[] = $connect->toArray();
    }

    return $connects;
  }

  /**
   * {@inheritdoc}
   */
  public function fetchDataBatch(array &$context) {
    if (empty($context['sandbox']['max'])) {
      $context['finished'] = 0;
      $context['sandbox']['max'] = $this->retsProcessor->numberOfQueueItems();
      $context['sandbox']['progress'] = 0;
      $context['message'] = $this->t('Loading rets data ...');
      $context['results']['updated'] = 0;
      $context['results']['failures'] = 0;
      $context['results']['processed'] = 0;
    }

    // Grab another item from the fetch queue.
    for ($i = 0; $i < 5; $i++) {
      if ($item = $this->retsProcessor->claimQueueItem()) {
        if ($this->retsProcessor->processFetchTask($item->data)) {
          $context['results']['updated']++;
          $context['message'] = $this->t('Loaded rets data for %title.', ['%title' => $item->data['info']['name']]);
        }
        else {
          $context['message'] = $this->t('Failed to load rets data for %title.', ['%title' => $item->data['info']['name']]);
          $context['results']['failures']++;
        }
        $context['sandbox']['progress']++;
        $context['results']['processed']++;
        $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
        $this->retsProcessor->deleteQueueItem($item);
      }
      else {
        $context['finished'] = 1;
        return;
      }
    }
  }

}
