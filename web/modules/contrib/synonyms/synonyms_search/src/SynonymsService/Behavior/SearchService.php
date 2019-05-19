<?php

namespace Drupal\synonyms_search\SynonymsService\Behavior;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\synonyms\Entity\Synonym;
use Drupal\synonyms\SynonymsProviderInterface\SynonymsGetProviderInterface;
use Drupal\synonyms\SynonymsService\Behavior\SynonymsBehaviorInterface;
use Drupal\synonyms\SynonymsService\BehaviorService;

/**
 * Expose synonyms of referenced entities to core Search index.
 */
class SearchService implements SynonymsBehaviorInterface {

  use StringTranslationTrait;

  /**
   * Behavior name this class represents.
   *
   * @var string
   */
  const behavior = 'synonyms.behavior.search';

  /**
   * @var EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var BehaviorService
   */
  protected $behaviorService;

  /**
   * @var QueryFactory
   */
  protected $queryFactory;

  /**
   * @var Connection
   */
  protected $database;

  /**
   * @var TimeInterface
   */
  protected $time;

  /**
   * SearchService constructor.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, BehaviorService $behavior_service, QueryFactory $query_factory, Connection $database, TimeInterface $time) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->behaviorService = $behavior_service;
    $this->queryFactory = $query_factory;
    $this->database = $database;
    $this->time = $time;
  }

  /**
   * Implementation of hook_entity_view().
   */
  public function entityView(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    if ($entity instanceof ContentEntityInterface && $view_mode == 'search_index') {
      $synonyms = [];
      $cacheable_metadata = new CacheableMetadata();

      $entity_references = array_filter($this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle()), function($item) {
        if ($item->getType() == 'entity_reference') {
          $target_entity_type = $this->entityTypeManager->getDefinition($item->getSetting('target_type'));
          return $target_entity_type instanceof ContentEntityTypeInterface;
        }
        return FALSE;
      });
      foreach ($entity_references as $entity_reference) {
        foreach ($entity->get($entity_reference->getName())->referencedEntities() as $target_entity) {
          $synonyms = array_merge($synonyms, $this->getEntitySynonyms($target_entity));
          $cacheable_metadata->addCacheableDependency($target_entity);

          // Depend on the synonyms configs for this entity type + bundle.
          $cacheable_metadata->addCacheTags([Synonym::cacheTagConstruct(self::behavior, $target_entity->getEntityTypeId(), $target_entity->bundle())]);
        }
      }

      $build['synonyms_search'] = [
        '#markup' => implode(', ', $synonyms),
      ];
      $cacheable_metadata->applyTo($build['synonyms_search']);
    }
  }

  /**
   * Mark all search index dependent on a given entity for reindexing.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity whose dependent search index should be marked for reindexing
   */
  public function entityMarkForReindex(ContentEntityInterface $entity) {
    $this->entityMarkForReindexMultiple([$entity->id()], $entity->getEntityTypeId());
  }

  /**
   * Mark all search index dependent on given entities for reindexing.
   *
   * @param array $entity_ids
   *   Entity IDs whose dependent search index should be marked for reindexing
   * @param string $entity_type
   *   Entity type of the give entity IDs
   */
  public function entityMarkForReindexMultiple($entity_ids, $entity_type) {
    if (empty($entity_ids)) {
      return;
    }

    $map = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');
    foreach ($map as $host_entity_type => $fields) {
      foreach ($fields as $field_name => $field_info) {
        $field = FieldStorageConfig::loadByName($host_entity_type, $field_name);
        if ($field && $field->getSetting('target_type') == $entity_type) {
          $query = $this->queryFactory->get($host_entity_type, 'AND');
          $query->condition($field_name, $entity_ids, 'IN');
          $result = $query->execute();

          // For the sake of performance we do a direct query on the
          // {search_dataset} table instead of using search_mark_for_reindex()
          // function.
          // TODO: is there any smarter way to generate search type rather then
          // adding suffix of '_search'?
          if (!empty($result)) {
            $this->database->update('search_dataset')
              ->fields(['reindex' => $this->time->getRequestTime()])
              ->condition('reindex', 0)
              ->condition('type', $host_entity_type . '_search')
              ->condition('sid', array_values($result), 'IN')
              ->execute();
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->t('Search');
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredInterfaces() {
    return [
      SynonymsGetProviderInterface::class,
    ];
  }

  /**
   * Retrieve a list of synonyms from a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return string[]
   *   A list of synonyms of a given entity
   */
  protected function getEntitySynonyms(ContentEntityInterface $entity) {
    $synonyms = [];

    foreach ($this->behaviorService->getSynonymConfigEntities('synonyms.behavior.search', $entity->getEntityTypeId(), $entity->bundle()) as $synonym_config) {
      $synonyms = array_merge($synonyms, $synonym_config->getProviderPluginInstance()->getSynonyms($entity));
    }

    return $synonyms;
  }

}
