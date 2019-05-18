<?php

namespace Drupal\usasearch;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Provides a repository for Search Page config entities.
 *
 * @implements UsasearchRepositoryInterface.
 *
 * Todo rename to usasearchIndexRepository.
 */
class UsasearchRepository {
  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The search page storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Constructs a new UsasearchRepository.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function setIndexableEntities() {
    $contentTypes = \Drupal::service('entity.manager')
      ->getStorage('node_type')
      ->loadMultiple();
    $api = \Drupal::service('usasearch.api');
    $enabled_types = $api->getEnabledContentTypes();
    foreach ($contentTypes as $contentType) {
      if (in_array($contentType->id(), $enabled_types)) {
        $nids = \Drupal::entityQuery('node')
          ->condition('type', $contentType->id())
          ->execute();
        $this->markReindex($nids);
      }
    }
  }

  /**
   * Updates digitalgovsearch index queue.
   *
   * @param array|int $nids
   *   An array of node ids.
   */
  public function markReindex($nids) {
    foreach ($nids as $nid) {
      $index_node = (int) usasearch_get_per_node_settings($nid);

      $query = \Drupal::database()->upsert('digitalgovsearch');
      $query->fields([
        'nid',
        'search_include',
        'reindex',
      ]);
      $query->values([
        $nid,
        $index_node,
        REQUEST_TIME,
      ]);
      $query->key('nid');
      $query->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexableEntities() {
    $config = \Drupal::config('usasearch.settings');
    $limit = $config->get('cron_limit');

    $query = \Drupal::database()->select('digitalgovsearch');
    $query->fields('digitalgovsearch', ['nid']);
    $query->condition('reindex', 0, '!=');
    $query->range(0, (int) $limit);
    $nids = $query->execute()->fetchAll();

    return $nids;
  }

  /**
   * Clears the digitalgovsearch index queue.
   *
   * Todo unused and this updates all database fields.
   *
   * @deprecated
   */
  public function clearIndexQueue() {
    \Drupal::database()->update('digitalgovsearch')
      ->condition('reindex', 0, '!=')
      ->fields([
        'reindex' => 0,
      ])
      ->execute();
  }

  /**
   * Returns an entity query instance.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The query instance.
   */
  protected function getQuery() {
    return $this->storage->getQuery();
  }

}
