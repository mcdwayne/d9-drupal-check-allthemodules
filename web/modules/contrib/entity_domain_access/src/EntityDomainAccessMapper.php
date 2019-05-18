<?php

namespace Drupal\entity_domain_access;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides fields operations for domain entity module fields.
 */
class EntityDomainAccessMapper {

  /**
   * The name of the access control field.
   */
  const FIELD_NAME = DOMAIN_ACCESS_FIELD;

  /**
   * The name of the all affiliates field.
   */
  const FIELD_ALL_NAME = DOMAIN_ACCESS_ALL_FIELD;

  /**
   * Domain entity behavior widget type, add a hidden field on entity.
   *
   * Entity is automatically assigned to the current domain (hidden for user).
   */
  const BEHAVIOR_AUTO = 'auto';

  /**
   * Domain entity behavior widget type, add a field on entity creation form.
   *
   * Allowing user to choose entity affiliation on creation/update form.
   */
  const BEHAVIOR_USER = 'user';

  const CAHCE_TAG = 'entity_domain_access_mapper';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Black list of entity types.
   *
   * @var array
   *
   * Domain access rules will not be allied for entity types from this list.
   * It will be helpful if domain access handler for entity type already exists.
   * For example, 'domain_access' module, which provide domain access handlers
   * for node and user.
   *
   * @todo Add interface to manage blacklist.
   */
  protected $blackListEntityType;

  /**
   * Contain array of enaled entity types.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $enabledEntityTypes = [];

  /**
   * Creates a new DomainEntityMapper object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache bin.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache) {
    $this->entityTypeManager = $entity_type_manager;

    $config = $config_factory->get('entity_domain_access.settings');
    $this->blackListEntityType = $config->get('black_list_entity_type');
    $this->cache = $cache;
  }

  /**
   * Returns entity types that have domain access field storage.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   Keyed array of enabled entity types.
   */
  public function getEnabledEntityTypes() {
    $cache_bin = $this->cache;

    $cid = self::CAHCE_TAG . '.' . __METHOD__;
    $enabled_entity_type_ids = $cache_bin->get($cid);
    $enabled_entity_type_ids = !empty($enabled_entity_type_ids) ? $enabled_entity_type_ids->data : [];

    if (empty($enabled_entity_type_ids)) {
      $enabled_entity_type_ids = [];
      $types = $this->getEntityTypes();
      foreach ($types as $id => $type) {
        if ($this->inBlackList($id)) {
          continue;
        }
        if ($this->loadFieldStorage($id, self::FIELD_NAME)) {
          $enabled_entity_type_ids[] = $id;
          $this->enabledEntityTypes[$id] = $type;
        }
      }
      $this->cache->set($cid, $enabled_entity_type_ids, $cache_bin::CACHE_PERMANENT, [self::CAHCE_TAG]);
    }
    elseif (empty($this->enabledEntityTypes)) {
      $this->enabledEntityTypes = $this->getEntityTypes($enabled_entity_type_ids);
    }

    return $this->enabledEntityTypes;
  }

  /**
   * Invalidate cache tags.
   */
  public function invalidateTags() {
    Cache::invalidateTags([self::CAHCE_TAG]);
  }

  /**
   * Check that entity type is blacklisted.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   *
   * @return bool
   *   An entity type presetn in black list or not.
   */
  public function inBlackList($entity_type_id) {
    return in_array($entity_type_id, $this->blackListEntityType);
  }

  /**
   * Check that entity type is handled by Entity Domain Access.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   *
   * @return bool
   *   Is handled Domain Access entity type or not.
   */
  public function isDomainAccessEntityType($entity_type_id) {
    return array_key_exists($entity_type_id, $this->getEnabledEntityTypes());
  }

  /**
   * Check that entity bundle is handled by Entity Domain Access.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $bundle_id
   *   Bundle ID.
   *
   * @return bool
   *   Is handled Domain Access entity type or not.
   */
  public function isDomainAccessEntityBundle($entity_type_id, $bundle_id) {
    return !$this->inBlackList($entity_type_id) && (bool) $this->loadField($entity_type_id, $bundle_id, self::FIELD_NAME);
  }

  /**
   * Check that entity can be handled by Entity Domain Access.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @return bool
   *   Is handled Domain Access entity or not.
   */
  public function isDomainAccessEntity(EntityInterface $entity) {
    return (bool) $this->isDomainAccessEntityBundle($entity->getEntityType()->id(), $entity->bundle());
  }

  /**
   * Returns fieldable entity type definitions.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   The fieldable entity types.
   */
  public function getEntityTypes(array $ids = []) {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $result = [];
    foreach ($entity_types as $entity_type_id => $entity_type) {
      // @todo Fix https://www.drupal.org/node/2842808 for 8.3.x core.
      $is_fieldable = $entity_type->isSubclassOf(FieldableEntityInterface::class);
      $return_all = empty($ids);
      if ($is_fieldable && ($return_all || in_array($entity_type_id, $ids))) {
        $result[$entity_type_id] = $entity_type;
      }
    }
    return $result;
  }

  /**
   * Loads field storage config.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $field_name
   *   Name of loading field.
   *
   * @return \Drupal\field\Entity\FieldStorageConfig|null
   *   The field storage or NULL.
   */
  public function loadFieldStorage($entity_type_id, $field_name) {
    $storage = $this->entityTypeManager->getStorage('field_storage_config');
    return $storage->load($entity_type_id . '.' . $field_name);
  }

  /**
   * Loads field from entity bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The entity type bundle name.
   * @param string $field_name
   *   Name of loading field.
   *
   * @return \Drupal\field\Entity\FieldConfig|null
   *   The field or NULL.
   */
  public function loadField($entity_type_id, $bundle, $field_name) {
    $storage = $this->entityTypeManager->getStorage('field_config');
    return $storage->load($entity_type_id . '.' . $bundle . '.' . $field_name);
  }

  /**
   * Deletes field storage.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   */
  public function deleteFieldStorage($entity_type_id) {
    $field_storage = $this->loadFieldStorage($entity_type_id, self::FIELD_NAME);
    if ($field_storage) {
      $field_storage->delete();
    }

    $field_storage = $this->loadFieldStorage($entity_type_id, self::FIELD_ALL_NAME);
    if ($field_storage) {
      $field_storage->delete();
    }
  }

  /**
   * Creates domain fields.
   *
   * @param string $entity_type
   *   The entity type machine name.
   * @param string $bundle
   *   The entity type's bundle.
   */
  public function addDomainField($entity_type, $bundle) {
    $field_storage = $this->createFieldStorage($entity_type);
    $field = FieldConfig::loadByName($entity_type, $bundle, self::FIELD_NAME);
    if (empty($field)) {
      $field = [
        'label' => 'Domain Access',
        // @Todo Add better naming for entities without bundles.
        'description' => 'Select the affiliate domain(s). If nothing was selected: Affiliated to all domains.',
        'bundle' => $bundle,
        'required' => FALSE,
        'field_storage' => $field_storage,
        'default_value_callback' => 'Drupal\entity_domain_access\EntityDomainAccessManager::getDefaultValue',
      ];

      $field = $this->entityTypeManager->getStorage('field_config')
        ->create($field);
      $field->save();

      // Assign widget settings for the 'default' form mode.
      $entity_form_display = $this->entityTypeManager->getStorage('entity_form_display')->load($entity_type . '.' . $bundle . '.default');
      if ($entity_form_display) {
        /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
        $entity_form_display->setComponent(self::FIELD_NAME, [
          'type' => 'options_buttons',
        ])->save();
      }

      // Assign display settings for the 'default' view mode.
      $entity_view_display = $this->entityTypeManager->getStorage('entity_view_display')->load($entity_type . '.' . $bundle . '.default');
      if ($entity_view_display) {
        /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_view_display */
        $entity_view_display->removeComponent(self::FIELD_NAME)->save();
      }
    }

    $field_storage = $this->createFieldAllAffilatesStorage($entity_type);
    $field = FieldConfig::loadByName($entity_type, $bundle, self::FIELD_ALL_NAME);
    if (empty($field)) {
      $field = [
        'label' => 'Send to all affiliates',
        // @Todo Add better naming for entities without bundles.
        'description' => 'Make this widget available on all domains.',
        'bundle' => $bundle,
        'required' => FALSE,
        'field_storage' => $field_storage,
        'default_value' => FALSE,
      ];

      $field = $this->entityTypeManager->getStorage('field_config')
        ->create($field);
      $field->save();

      // Assign widget settings for the 'default' form mode.
      $entity_form_display = $this->entityTypeManager->getStorage('entity_form_display')->load($entity_type . '.' . $bundle . '.default');
      if ($entity_form_display) {
        /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
        $entity_form_display->setComponent(self::FIELD_ALL_NAME, [
          'type' => 'options_buttons',
        ])->save();
      }

      // Assign widget settings for the 'default' form mode.
      $entity_form_display = $this->entityTypeManager->getStorage('entity_form_display')->load($entity_type . '.' . $bundle . '.default');
      if ($entity_form_display) {
        /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $entity_form_display */
        $entity_form_display->setComponent(self::FIELD_ALL_NAME, [
          'type' => 'options_buttons',
        ])->save();
      }

      // Assign display settings for the 'default' view mode.
      $entity_view_display = $this->entityTypeManager->getStorage('entity_view_display')->load($entity_type . '.' . $bundle . '.default');
      if ($entity_view_display) {
        /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $entity_view_display */
        $entity_view_display->removeComponent(self::FIELD_ALL_NAME)->save();
      }
    }
  }

  /**
   * Creates field storage for 'field_domain_access'.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\field\Entity\FieldStorageConfig
   *   The field storage.
   */
  public function createFieldStorage($entity_type_id) {
    if ($field_storage = $this->loadFieldStorage($entity_type_id, self::FIELD_NAME)) {
      // Prevent creation of existing field storage.
      return $field_storage;
    }
    $storage = $this->entityTypeManager->getStorage('field_storage_config');
    $field_storage = $storage->create([
      'entity_type' => $entity_type_id,
      'field_name' => self::FIELD_NAME,
      'type' => 'entity_reference',
      'persist_with_no_fields' => FALSE,
      'locked' => FALSE,
    ]);
    $field_storage
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSetting('target_type', 'domain')
      ->save();
    return $field_storage;
  }

  /**
   * Creates field storage for 'field_domain_all_affiliates'.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\field\Entity\FieldStorageConfig
   *   The field storage.
   */
  public function createFieldAllAffilatesStorage($entity_type_id) {
    if ($field_storage = $this->loadFieldStorage($entity_type_id, self::FIELD_ALL_NAME)) {
      // Prevent creation of existing field storage.
      return $field_storage;
    }
    $storage = $this->entityTypeManager->getStorage('field_storage_config');
    $field_storage = $storage->create([
      'entity_type' => $entity_type_id,
      'field_name' => self::FIELD_ALL_NAME,
      'type' => 'boolean',
      'persist_with_no_fields' => FALSE,
      'locked' => FALSE,
    ]);
    $field_storage
      ->setCardinality(1)
      ->save();
    return $field_storage;
  }

}
