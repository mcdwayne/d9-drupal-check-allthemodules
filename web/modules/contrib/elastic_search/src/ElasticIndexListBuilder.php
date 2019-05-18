<?php

namespace Drupal\elastic_search;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\elastic_search\Entity\ElasticIndexInterface;
use Drupal\elastic_search\Entity\FieldableEntityMap;
use Elasticsearch\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Elastic index entities.
 */
class ElasticIndexListBuilder extends ConfigEntityListBuilder {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var Client
   */
  protected $client;

  /**
   * {@inheritdoc}
   *
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function createInstance(ContainerInterface $container,
                                        EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity_type.manager'),
      $container->get('elastic_search.connection_factory')
                ->getElasticConnection()
    );
  }

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface        $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface     $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Elasticsearch\Client                          $client
   */
  public function __construct(EntityTypeInterface $entity_type,
                              EntityStorageInterface $storage,
                              EntityTypeManagerInterface $entityTypeManager,
                              Client $client) {
    $this->entityTypeId = $entity_type->id();
    $this->storage = $storage;
    $this->entityType = $entity_type;
    $this->entityTypeManager = $entityTypeManager;
    $this->client = $client;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Elastic index');
    $header['id'] = $this->t('Machine name');
    $header['mapping_update'] = $this->t('Needs Mapping Update');
    $header['documents'] = $this->t('indexed/total');
    $header['entity_map'] = $this->t('Fieldable Entity Map');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var $entity ElasticIndexInterface */
    $row['label'] = $entity->getIndexName();
    $row['id'] = $entity->id();
    $row['mapping_update'] = $entity->needsUpdate() ? 'true' : 'false';
    $row['documents'] = $this->getDocumentCount($entity);
    $row['entity_map'] = $entity->getMappingEntityId();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return string
   */
  protected function getDocumentCount(EntityInterface $entity) {
    $elasticCount = $this->getElasticCount($entity);
    $drupalCount = $this->getDrupalCount($entity);
    return $elasticCount . '/' . $drupalCount;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return int|string
   */
  protected function getElasticCount(EntityInterface $entity) {
    $count = 0;
    try {
      if ($this->client->indices()
                       ->exists(['index' => $entity->getIndexName()])
      ) {
        $params = ['index' => $entity->getIndexName()];
        $countResult = $this->client->count($params);
        $count = $countResult['count'];
      }
    } catch (\Throwable $t) {
      //Do nothing
      return 'error';
    }

    return $count;
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return array|int|string
   */
  protected function getDrupalCount(EntityInterface $entity) {
    /** @var \Drupal\elastic_search\Entity\ElasticIndex $entity */
    $id = FieldableEntityMap::getEntityAndBundle($entity->getMappingEntityId());

    try {
      $storage = $this->entityTypeManager->getStorage($id['entity']);
    } catch (\Throwable $t) {
      return 'error';
    }

    //TODO - Surely there must be something more generic than this nightmare?
    $query = $storage->getQuery()->count();
    $query->condition('type', $id['bundle']);
    $query->condition('langcode', $entity->getIndexLanguage());
    try {
      $dCount = $query->execute();
    } catch (\Throwable $t) {
      $query = $storage->getQuery()->count();
      $query->condition('bundle', $id['bundle']);
      $query->condition('langcode', $entity->getIndexLanguage());
      try {
        $dCount = $query->execute();
      } catch (\Throwable $t) {
        $query = $storage->getQuery()->count();
        $query->condition('vid', $id['bundle']);
        $query->condition('langcode', $entity->getIndexLanguage());
        try {
          $dCount = $query->execute();
        } catch (\Throwable $t) {
          $dCount = 'error';
        }
      }
    }
    return $dCount;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    $operations['updateServer'] = [
      'title'  => $this->t('Server mapping update'),
      'weight' => 1,
      'url'    => Url::fromRoute('elastic_search.controller.index.update',
                                 ['elastic_index' => $entity->id()]),
    ];

    $operations['delete'] = [
      'title'  => $this->t('Delete'),
      'weight' => 4,
      'url'    => Url::fromRoute('elastic_search.controller.index.delete',
                                 ['elastic_index' => $entity->id()]),
    ];

    $operations['deleteRemote'] = [
      'title'  => $this->t('Delete remote'),
      'weight' => 4,
      'url'    => Url::fromRoute('elastic_search.controller.index.delete_remote',
                                 ['elastic_index' => $entity->id()]),
    ];

    $operations['clearServer'] = [
      'title'  => $this->t('Server document clear'),
      'weight' => 3,
      'url'    => Url::fromRoute('elastic_search.controller.index.clear',
                                 ['elastic_index' => $entity->id()]),
    ];

    $operations['bulkUpdateServer'] = [
      'title'  => $this->t('Server document update'),
      'weight' => 2,
      'url'    => Url::fromRoute('elastic_search.controller.index.document.update',
                                 ['elastic_index' => $entity->id()]),
    ];

    return $operations;
  }

}
