<?php

namespace Drupal\prepared_data\Plugin\prepared_data\Provider;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\prepared_data\PreparedDataInterface;
use Drupal\prepared_data\Provider\ProviderBase;
use Drupal\prepared_data\Provider\ProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A data provider for entities of any type.
 *
 * The granularity of this provider is limited to entity ID and language.
 * It does not support revisioning.
 *
 * @PreparedDataProvider(
 *   id = "entity",
 *   label = @Translation("Entity data provider"),
 *   priority = 10
 * )
 */
class EntityDataProvider extends ProviderBase implements ProviderInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The entity memory cache, if available.
   *
   * The entity memory cache was added in Drupal 8.6.
   * @see https://www.drupal.org/project/drupal/issues/1596472
   *
   * @var \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface|null
   */
  protected $entityMemoryCache;

  /**
   * Holds a list of known entities.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $knownEntities = [];

  /**
   * Holds a list of known next matches, keyed by partial.
   *
   * @var array
   */
  protected $nextMatches = [];

  /**
   * Keeps in mind how many times an entity has been loaded.
   *
   * This is used to reset the entity in-memory cache
   * to prevent memory exceedance, especially on batch processing.
   *
   * @var int
   */
  protected $entityLoadCount = 0;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = $container->get('entity.repository');
    /** @var \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $entity_memory_cache */
    $entity_memory_cache = NULL;
    if ($container->has('entity.memory_cache')) {
      $entity_memory_cache = $container->get('entity.memory_cache');
    }
    return new static($entity_type_manager, $entity_repository, $entity_memory_cache, $configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Constructs an EntityDataProvider object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $entity_memory_cache
   *   The entity memory cache, if available.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityRepositoryInterface $entity_repository, MemoryCacheInterface $entity_memory_cache = NULL, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityRepository = $entity_repository;
    $this->entityMemoryCache = $entity_memory_cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyPattern() {
    return 'entity:{entity_type}:{id}:{langcode}';
  }

  /**
   * {@inheritdoc}
   */
  public function match($argument) {
    if ($argument instanceof EntityInterface) {
      $entity = $argument;
      // Prefer UUIDs to be able to prepare data for new entities.
      $id = $entity->uuid() ? $entity->uuid() : $entity->id();
      if (NULL === $id) {
        return FALSE;
      }
      $this->knownEntities[$id] = $entity;
      $parameters = [
        '{entity_type}' => $entity->getEntityTypeId(),
        '{id}' => $id,
        '{langcode}' => $entity->language()->getId(),
      ];
      return strtr($this->getKeyPattern(), $parameters);
    }
    return parent::match($argument);
  }

  /**
   * Get the next / closest key which matches to the given key partial.
   *
   * This method is regularly being used
   * for building up prepared data via batch processing.
   *
   * @param string $partial
   *   Either a partial or complete key.
   *   The partial must at least begin
   *   with entity:{entity_type}, e.g. entity:node.
   * @param bool $reset
   *   (Optional) Whether iteration should be reset.
   *   Default is set to FALSE (no reset).
   *
   * @return string|false
   *   The matched key, or FALSE if no match was found.
   */
  public function nextMatch($partial = NULL, $reset = FALSE) {
    if (TRUE === $reset) {
      $this->nextMatches = [];
    }
    if (empty($partial)) {
      return FALSE;
    }
    elseif (!empty($this->nextMatches[$partial])) {
      return array_pop($this->nextMatches[$partial]);
    }

    $params = $this->getParameters($partial);
    if (!isset($params['entity_type'])) {
      // At least entity:{entity_type} is required inside the partial.
      return FALSE;
    }

    // Clear in-memory caching to prevent memory exceedance.
    $this->clearStaticEntityCaches();

    try {
      $storage = $this->entityTypeManager->getStorage($params['entity_type']);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    if ($match = parent::nextMatch($partial, $reset)) {
      // Match would only happen on a complete key,
      // thus reset the iterator.
      $this->nextMatchIterator = -1;
      return $match;
    }

    $entity = NULL;
    if (isset($params['id'])) {
      $entity = $storage->load($params['id']);
    }
    else {
      $query = $storage->getQuery();
      $query->range($this->nextMatchIterator, 1);
      foreach ($query->execute() as $id) {
        $entity = $storage->load($id);
        break;
      }
    }
    if (empty($entity)) {
      // Reset the iterator to start from the beginning.
      $this->nextMatchIterator = -1;
      return FALSE;
    }

    // Collect all available translations.
    $langcodes = [$entity->language()->getId()];
    if ($entity instanceof TranslatableInterface) {
      $translations = $entity->getTranslationLanguages(TRUE);
      foreach (array_keys($translations) as $langcode) {
        if ($langcodes[0] !== $langcode) {
          $langcodes[] = $langcode;
        }
      }
    }

    // Build up the keys.
    $matches = [];
    $params['id'] = $entity->uuid() ? $entity->uuid() : $entity->id();
    foreach ($langcodes as $langcode) {
      $params['langcode'] = $langcode;
      $matches[] = $this->buildKeyForParams($params);
    }
    $this->nextMatches[$partial] = $matches;
    return $this->nextMatch($partial, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $argument) {
    if (!($key = $this->match($argument))) {
      return FALSE;
    }
    $parameters = $this->getParameters($key);
    if (!($entity = $this->getEntityForParams($parameters))) {
      return FALSE;
    }
    return $entity->access('view', $account, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildKeyForParams(array $parameters) {
    if (!($entity = $this->getEntityForParams($parameters))) {
      return FALSE;
    }
    // Enforce preferation of UUIDs instead of regular entity IDs.
    // Data can also be prepared for new, unsaved entities then.
    $parameters['id'] = $entity->uuid() ? $entity->uuid() : $entity->id();
    return parent::buildKeyForParams($parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(PreparedDataInterface $data) {
    // Add the entity as information.
    $key = $data->key();
    $params = $this->getParameters($key);
    $entity = $this->getEntityForParams($params);
    if (!$entity && (strpos($key, 'entity:') === 0)) {
      if (isset($params['entity_type'], $params['id'])) {
        // The requested entity does not exists,
        // thus flag this data to be deleted too.
        $data->shouldDelete(TRUE);
      }
    }
    $data->info()['entity'] = $entity;
  }

  /**
   * Loads and returns the entity for the given parameters.
   *
   * @param array $parameters
   *   The parameters, keyed by parameter name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   The entity if found, FALSE otherwise.
   */
  protected function getEntityForParams(array $parameters) {
    if (!isset($parameters['entity_type'], $parameters['id'], $parameters['langcode'])) {
      return FALSE;
    }
    $type = $parameters['entity_type'];
    $id = $parameters['id'];
    $langcode = $parameters['langcode'];
    try {
      $entity_storage = $this->entityTypeManager->getStorage($type);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    if (isset($this->knownEntities[$id])) {
      $entity = $this->knownEntities[$id];
    }
    elseif (substr_count($id, '-') === 4) {
      try {
        if (!($entity = $this->entityRepository->loadEntityByUuid($type, $id))) {
          return FALSE;
        }
      }
      catch (EntityStorageException $e) {
        if (!($entity = $entity_storage->load($id))) {
          return FALSE;
        }
      }
    }
    elseif (!($entity = $entity_storage->load($id))) {
      return FALSE;
    }

    if (($entity instanceof TranslatableInterface) && ($entity->language()->getId() !== $langcode)) {
      if (!$entity->hasTranslation($langcode)) {
        return FALSE;
      }
      $entity = $entity->getTranslation($langcode);
    }
    $this->entityLoadCount++;
    if ($this->entityLoadCount > 40) {
      // Clear in-memory caching to prevent memory exceedance.
      $this->entityLoadCount = 0;
      $this->clearStaticEntityCaches();
      $this->knownEntities = [];
    }
    $this->knownEntities[$id] = $entity;
    return $entity;
  }

  /**
   * Clears in-memory caching to prevent memory exceedance.
   */
  protected function clearStaticEntityCaches() {
    // Clear in-memory cached entities, if available.
    if (isset($this->entityMemoryCache)) {
      $this->entityMemoryCache->deleteAll();
    }
    // Reset any static cache of entity type handlers.
    $this->entityTypeManager->useCaches(FALSE);
    // Enable caching again on them.
    $this->entityTypeManager->useCaches(TRUE);
  }

}
