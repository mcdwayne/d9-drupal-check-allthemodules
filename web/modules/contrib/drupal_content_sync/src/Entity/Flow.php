<?php

namespace Drupal\drupal_content_sync\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\drupal_content_sync\ApiUnifyFlowExport;
use Drupal\drupal_content_sync\ExportIntent;
use Drupal\drupal_content_sync\ImportIntent;
use Drupal\drupal_content_sync\SyncIntent;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the Flow entity.
 *
 * @ConfigEntityType(
 *   id = "dcs_flow",
 *   label = @Translation("Flow"),
 *   handlers = {
 *     "list_builder" = "Drupal\drupal_content_sync\Controller\FlowListBuilder",
 *     "form" = {
 *       "add" = "Drupal\drupal_content_sync\Form\FlowForm",
 *       "edit" = "Drupal\drupal_content_sync\Form\FlowForm",
 *       "delete" = "Drupal\drupal_content_sync\Form\FlowDeleteForm",
 *     }
 *   },
 *   config_prefix = "flow",
 *   admin_permission = "administer drupal content sync:",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/services/drupal_content_sync/synchronizations/{dcs_flow}/edit",
 *     "delete-form" = "/admin/config/services/drupal_content_sync/synchronizations/{dcs_flow}/delete",
 *   }
 * )
 */
class Flow extends ConfigEntityBase implements FlowInterface {
  /**
   * @var string HANDLER_IGNORE
   *    Ignore this entity type / bundle / field completely.
   */
  const HANDLER_IGNORE = 'ignore';

  /**
   * @var string PREVIEW_DISABLED
   *    Hide these entities completely.
   */
  const PREVIEW_DISABLED = 'disabled';

  /**
   * @var string PREVIEW_TABLE
   *    Show these entities in a table view.
   */
  const PREVIEW_TABLE = 'table';

  /**
   * The Flow ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Flow name.
   *
   * @var string
   */
  public $name;

  /**
   * The Flow entities.
   *
   * @TODO Refactor to use $entities and within that add the ['fields'] config
   *
   * @var array
   */
  public $sync_entities;

  /**
   * Ensure that pools are imported before the flows.
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $pools = Pool::getAll();
    foreach ($pools as $pool) {
      $this->addDependency('config', 'drupal_content_sync.pool.' . $pool->id);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    try {
      foreach ($entities as $entity) {
        $exporter = new ApiUnifyFlowExport($entity);
        $exporter->remove(FALSE);
      }
    }
    catch (RequestException $e) {
      $messenger = \Drupal::messenger();
      $messenger->addError(t('The API Unify server could not be accessed. Please check the connection.'));
      throw new AccessDeniedHttpException();
    }
  }

  /**
   * Get a unique version hash for the configuration of the provided entity type
   * and bundle.
   *
   * @param string $type_name
   *   The entity type in question.
   * @param string $bundle_name
   *   The bundle in question.
   *
   * @return string
   *   A 32 character MD5 hash of all important configuration for this entity
   *   type and bundle, representing it's current state and allowing potential
   *   conflicts from entity type updates to be handled smoothly.
   */
  public static function getEntityTypeVersion($type_name, $bundle_name) {
    $class = \Drupal::entityTypeManager()
      ->getDefinition($type_name)
      ->getOriginalClass();
    $interface = 'Drupal\Core\Entity\FieldableEntityInterface';
    if (in_array($interface, class_implements($class))) {
      $entityFieldManager = \Drupal::service('entity_field.manager');
      $field_definitions = $entityFieldManager->getFieldDefinitions($type_name, $bundle_name);

      $field_definitions_array = (array) $field_definitions;
      unset($field_definitions_array['field_drupal_content_synced']);

      $field_names = array_keys($field_definitions_array);
      sort($field_names);

      $version = json_encode($field_names);
    }
    else {
      $version = '';
    }

    $version = md5($version);
    return $version;
  }

  /**
   * Check whether the local deletion of the given entity is allowed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return bool
   */
  public static function isLocalDeletionAllowed(EntityInterface $entity) {
    if (!$entity->uuid()) {
      return TRUE;
    }
    $meta_infos = MetaInformation::getInfosForEntity(
      $entity->getEntityTypeId(),
      $entity->uuid()
    );
    foreach ($meta_infos as $info) {
      if (!$info->getLastImport() || $info->isSourceEntity()) {
        continue;
      }
      $flow = $info->getFlow();
      $config = $flow->getEntityTypeConfig($entity->getEntityTypeId(), $entity->bundle());
      if (!boolval($config['import_deletion_settings']['allow_local_deletion_of_import'])) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Get the correct synchronization for a specific action on a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $reason
   * @param string $action
   *
   * @return \Drupal\drupal_content_sync\Entity\Flow[]
   */
  public static function getFlowsForEntity(EntityInterface $entity, $reason, $action = SyncIntent::ACTION_CREATE) {
    $flows = self::getAll();

    $result = [];

    foreach ($flows as $flow) {
      if ($flow->canExportEntity($entity, $reason, $action)) {
        $result[] = $flow;
      }
    }

    return $result;
  }

  /**
   * Get a list of all pools that are used for exporting this entity, either
   * automatically or manually selected.
   *
   * @param string $entity_type
   * @param string $bundle
   * @param string $reason
   *   {@see Flow::EXPORT_*}.
   * @param string $action
   *   {@see ::ACTION_*}.
   *
   * @return \Drupal\drupal_content_sync\Entity\Pool[]
   */
  public function getUsedImportPools($entity_type, $bundle) {
    $config = $this->getEntityTypeConfig($entity_type, $bundle);

    $result = [];
    $pools = Pool::getAll();

    foreach ($config['import_pools'] as $id => $setting) {
      $pool = $pools[$id];

      if ($setting == Pool::POOL_USAGE_FORBID) {
        continue;
      }

      $result[] = $pool;
    }

    return $result;
  }

  /**
   * Get a list of all pools that are used for exporting this entity, either
   * automatically or manually selected.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string|null $reason
   *   {@see Flow::EXPORT_*}.
   * @param string $action
   *   {@see ::ACTION_*}.
   * @param bool $include_forced
   *   Include forced pools. Otherwise only use-selected / referenced ones.
   *
   * @return \Drupal\drupal_content_sync\Entity\Pool[]
   */
  public function getUsedExportPools(EntityInterface $entity, $reason, $action, $include_forced = TRUE) {
    $config = $this->getEntityTypeConfig($entity->getEntityTypeId(), $entity->bundle());
    if (!$this->canExportEntity($entity, $reason, $action)) {
      return [];
    }

    $result = [];
    $pools = Pool::getAll();

    foreach ($config['export_pools'] as $id => $setting) {
      $pool = $pools[$id];

      if ($setting == Pool::POOL_USAGE_FORBID) {
        continue;
      }

      if ($setting == Pool::POOL_USAGE_FORCE) {
        if ($include_forced) {
          $result[$id] = $pool;
        }
        continue;
      }

      $meta = MetaInformation::getInfoForEntity($entity->getEntityTypeId(), $entity->uuid(), $this, $pool);
      if ($meta && $meta->isExportEnabled()) {
        $result[$id] = $pool;
      }
    }

    return $result;
  }

  /**
   * Ask this synchronization whether or not it can export the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $reason
   * @param string $action
   *
   * @return bool
   */
  public function canExportEntity(EntityInterface $entity, $reason, $action = SyncIntent::ACTION_CREATE) {
    $config = $this->getEntityTypeConfig($entity->getEntityTypeId(), $entity->bundle());
    if (empty($config) || $config['handler'] == self::HANDLER_IGNORE) {
      return FALSE;
    }

    if ($action == SyncIntent::ACTION_DELETE && !boolval($config['export_deletion_settings']['export_deletion'])) {
      return FALSE;
    }

    if ($reason === ExportIntent::EXPORT_ANY) {
      return TRUE;
    }

    /**
     * If any handler is available, we can export this entity.
     */
    if ($reason == ExportIntent::EXPORT_FORCED || $config['export'] == ExportIntent::EXPORT_AUTOMATICALLY) {
      return TRUE;
    }

    return $config['export'] == $reason;
  }

  /**
   * @var \Drupal\drupal_content_sync\Entity\Flow[]
   *   All content synchronization configs. Use {@see Flow::getAll}
   *   to request them.
   */
  public static $all = NULL;

  /**
   * Load all entities.
   *
   * Load all dcs_flow entities and add overrides from global $config.
   *
   * @return \Drupal\drupal_content_sync\Entity\Flow[]
   */
  public static function getAll() {
    if (self::$all !== NULL) {
      return self::$all;
    }

    /**
     * @var \Drupal\drupal_content_sync\Entity\Flow[] $configurations
     */
    $configurations = \Drupal::entityTypeManager()
      ->getStorage('dcs_flow')
      ->loadMultiple();

    foreach ($configurations as $id => &$configuration) {
      global $config;
      $config_name = 'drupal_content_sync.flow.' . $id;
      if (!isset($config[$config_name]) || empty($config[$config_name])) {
        continue;
      }
      foreach ($config[$config_name] as $key => $new_value) {
        $configuration->set($key, $new_value);
      }
      $configuration->getEntityTypeConfig();
    }

    return self::$all = $configurations;
  }

  /**
   * Get all synchronizations that allow the provided entity import.
   *
   * @param string $entity_type_name
   * @param string $bundle_name
   * @param string $reason
   * @param string $action
   *
   * @return \Drupal\drupal_content_sync\Entity\Flow[]
   */
  public static function getImportSynchronizationsForEntityType($entity_type_name, $bundle_name, $reason, $action = SyncIntent::ACTION_CREATE) {
    $flows = self::getAll();

    $result = [];

    foreach ($flows as $flow) {
      if ($flow->canImportEntity($entity_type_name, $bundle_name, $reason, $action)) {
        $result[] = $flow;
      }
    }

    return $result;
  }

  /**
   * Get the first synchronization that allows the import of the provided entity
   * type.
   *
   * @param \Drupal\drupal_content_sync\Entity\Pool $pool
   * @param string $entity_type_name
   * @param string $bundle_name
   * @param string $reason
   * @param string $action
   *
   * @return \Drupal\drupal_content_sync\Entity\Flow|null
   */
  public static function getFlowForApiAndEntityType($pool, $entity_type_name, $bundle_name, $reason, $action = SyncIntent::ACTION_CREATE) {
    $flows = self::getAll();

    foreach ($flows as $flow) {
      if (!$flow->canImportEntity($entity_type_name, $bundle_name, $reason, $action)) {
        continue;
      }
      if ($pool && !in_array($pool, $flow->getUsedImportPools($entity_type_name, $bundle_name))) {
        continue;
      }

      return $flow;
    }

    return NULL;
  }

  /**
   * Ask this synchronization whether or not it can export the provided entity.
   *
   * @param string $entity_type_name
   * @param string $bundle_name
   * @param string $reason
   * @param string $action
   *
   * @return bool
   */
  public function canImportEntity($entity_type_name, $bundle_name, $reason, $action = SyncIntent::ACTION_CREATE) {
    $config = $this->getEntityTypeConfig($entity_type_name, $bundle_name);
    if (empty($config) || $config['handler'] == self::HANDLER_IGNORE) {
      return FALSE;
    }
    if ($action == SyncIntent::ACTION_DELETE && !boolval($config['import_deletion_settings']['import_deletion'])) {
      return FALSE;
    }
    // If any handler is available, we can import this entity.
    if ($reason == ImportIntent::IMPORT_FORCED) {
      return TRUE;
    }
    // Once imported manually, updates will arrive automatically.
    if ($reason == ImportIntent::IMPORT_AUTOMATICALLY && $config['import'] == ImportIntent::IMPORT_MANUALLY) {
      if ($action == SyncIntent::ACTION_UPDATE || $action == SyncIntent::ACTION_DELETE) {
        return TRUE;
      }
    }
    return $config['import'] == $reason;
  }

  /**
   * Ask this synchronization whether it supports the provided entity.
   * Returns false if either the entity type is not known or the config handler
   * is set to {@see Flow::HANDLER_IGNORE}.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return bool
   */
  public function supportsEntity(EntityInterface $entity) {
    return $this->getEntityTypeConfig($entity->getEntityTypeId(), $entity->bundle())['handler'] != self::HANDLER_IGNORE;
  }

  /**
   * Get the config for the given entity type or all entity types.
   *
   * @param string $entity_type
   * @param string $entity_bundle
   *
   * @return array
   */
  public function getEntityTypeConfig($entity_type = NULL, $entity_bundle = NULL) {
    $entity_types = $this->sync_entities;

    $result = [];

    foreach ($entity_types as $id => &$type) {
      // Ignore field definitions.
      if (substr_count($id, '-') != 1) {
        continue;
      }

      preg_match('/^(.+)-(.+)$/', $id, $matches);

      $entity_type_name = $matches[1];
      $bundle_name      = $matches[2];

      if ($entity_type && $entity_type_name != $entity_type) {
        continue;
      }
      if ($entity_bundle && $bundle_name != $entity_bundle) {
        continue;
      }

      // If this is called before being saved, we want to have version etc.
      // available still.
      if (empty($type['version'])) {
        $type['version']          = Flow::getEntityTypeVersion($entity_type_name, $bundle_name);
        $type['entity_type_name'] = $entity_type_name;
        $type['bundle_name']      = $bundle_name;
      }

      if ($entity_type && $entity_bundle) {
        return $type;
      }

      $result[$id] = $type;
    }

    return $result;
  }

  /**
   * The the entity type handler for the given config.
   *
   * @param $config
   *   {@see Flow::getEntityTypeConfig()}
   *
   * @return \Drupal\drupal_content_sync\Plugin\EntityHandlerInterface
   */
  public function getEntityTypeHandler($config) {
    $entityPluginManager = \Drupal::service('plugin.manager.dcs_entity_handler');

    $handler = $entityPluginManager->createInstance(
      $config['handler'],
      [
        'entity_type_name' => $config['entity_type_name'],
        'bundle_name' => $config['bundle_name'],
        'settings' => $config,
        'sync' => $this,
      ]
    );

    return $handler;
  }

  /**
   * Get the correct field handler instance for this entity type and field
   * config.
   *
   * @param $entity_type_name
   * @param $bundle_name
   * @param $field_name
   *
   * @return \Drupal\drupal_content_sync\Plugin\FieldHandlerInterface
   */
  public function getFieldHandler($entity_type_name, $bundle_name, $field_name) {
    $fieldPluginManager = \Drupal::service('plugin.manager.dcs_field_handler');

    $key = $entity_type_name . '-' . $bundle_name . '-' . $field_name;
    if (empty($this->sync_entities[$key])) {
      return NULL;
    }

    if ($this->sync_entities[$key]['handler'] == self::HANDLER_IGNORE) {
      return NULL;
    }

    $entityFieldManager = \Drupal::service('entity_field.manager');
    $field_definition = $entityFieldManager->getFieldDefinitions($entity_type_name, $bundle_name)[$field_name];

    $handler = $fieldPluginManager->createInstance(
      $this->sync_entities[$key]['handler'],
      [
        'entity_type_name' => $entity_type_name,
        'bundle_name' => $bundle_name,
        'field_name' => $field_name,
        'field_definition' => $field_definition,
        'settings' => $this->sync_entities[$key],
        'sync' => $this,
      ]
    );

    return $handler;
  }

  /**
   * Get the settings for the given field.
   *
   * @param $entity_type_name
   * @param $bundle_name
   * @param $field_name
   *
   * @return array
   */
  public function getFieldHandlerConfig($entity_type_name, $bundle_name, $field_name) {
    $key = $entity_type_name . '-' . $bundle_name . '-' . $field_name;
    return $this->sync_entities[$key];
  }

  /**
   * Get the preview type.
   *
   * @param $entity_type_name
   * @param $bundle_name
   *
   * @return string
   */
  public function getPreviewType($entity_type_name, $bundle_name) {
    $key = $entity_type_name . '-' . $bundle_name;
    $settings = $this->sync_entities[$key];
    if (empty($settings['preview'])) {
      return Flow::PREVIEW_DISABLED;
    }
    else {
      return $settings['preview'];
    }

  }

}
