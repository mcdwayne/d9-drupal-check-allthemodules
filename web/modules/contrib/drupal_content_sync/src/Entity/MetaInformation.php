<?php

namespace Drupal\drupal_content_sync\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the meta information entity type.
 *
 * @ingroup dcs_meta_info
 *
 * @ContentEntityType(
 *   id = "dcs_meta_info",
 *   label = @Translation("Meta Information"),
 *   base_table = "dcs_meta_info",
 *   entity_keys = {
 *     "id" = "id",
 *     "flow" = "flow",
 *     "pool" = "pool",
 *     "entity_uuid" = "entity_uuid",
 *     "entity_type" = "entity_type",
 *     "entity_type_version" = "entity_type_version",
 *     "flags" = "flags",
 *   },
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 * )
 */
class MetaInformation extends ContentEntityBase implements MetaInformationInterface {

  use EntityChangedTrait;

  const FLAG_UNUSED_CLONED             = 0x00000001;
  const FLAG_DELETED                   = 0x00000002;
  const FLAG_USER_ALLOWED_EXPORT       = 0x00000004;
  const FLAG_EDIT_OVERRIDE             = 0x00000008;
  const FLAG_IS_SOURCE_ENTITY          = 0x00000010;
  const FLAG_EXPORT_ENABLED            = 0x00000020;
  const FLAG_DEPENDENCY_EXPORT_ENABLED = 0x00000040;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    // Set Entity ID or UUID by default one or the other is not set.
    if (!isset($values['entity_type'])) {
      throw new \Exception(t('The type of the entity is required.'));
    }
    if (!isset($values['flow'])) {
      throw new \Exception(t('The flow is required.'));
    }
    if (!isset($values['pool'])) {
      throw new \Exception(t('The pool is required.'));
    }

    /**
     * @var \Drupal\Core\Entity\FieldableEntityInterface $entity
     */
    $entity = \Drupal::service('entity.repository')->loadEntityByUuid($values['entity_type'], $values['entity_uuid']);

    if (!isset($values['entity_type_version'])) {
      $values['entity_type_version'] = Flow::getEntityTypeVersion($entity->getEntityType()->id(), $entity->bundle());
      return;
    }
  }

  /**
   * @param string $entity_type
   * @param string $entity_uuid
   * @param \Drupal\drupal_content_sync\Entity\Pool $pool
   *
   * @return \Drupal\drupal_content_sync\Entity\MetaInformation[]
   */
  public static function getInfoForPool($entity_type, $entity_uuid, Pool $pool) {
    if (!$entity_type) {
      throw new \Exception('$entity_type is required.');
    }
    if (!$entity_uuid) {
      throw new \Exception('$entity_uuid is required.');
    }
    /**
     * @var \Drupal\drupal_content_sync\Entity\MetaInformation[] $entities
     */
    $entities = \Drupal::entityTypeManager()
      ->getStorage('dcs_meta_info')
      ->loadByProperties([
        'entity_type' => $entity_type,
        'entity_uuid' => $entity_uuid,
        'pool'        => $pool->id,
      ]);

    return $entities;
  }

  /**
   * Get a list of all meta information entities for the given entity.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $entity_uuid
   *   The entity UUID.
   * @param array $filter
   *   Additional filters. Usually "flow"=>... or "pool"=>...
   *
   * @return \Drupal\drupal_content_sync\Entity\MetaInformation[]
   */
  public static function getInfosForEntity($entity_type, $entity_uuid, $filter = NULL) {
    if (!$entity_type) {
      throw new \Exception('$entity_type is required.');
    }
    if (!$entity_uuid) {
      throw new \Exception('$entity_uuid is required.');
    }
    $base_filter = [
      'entity_type' => $entity_type,
      'entity_uuid' => $entity_uuid,
    ];

    /**
     * @var \Drupal\drupal_content_sync\Entity\MetaInformation[] $entities
     */
    $entities = \Drupal::entityTypeManager()
      ->getStorage('dcs_meta_info')
      ->loadByProperties($filter ? array_merge($filter, $base_filter) : $base_filter);

    return array_values($entities);
  }

  /**
   * @param string $entity_type
   * @param string $entity_uuid
   * @param \Drupal\drupal_content_sync\Entity\Flow $flow
   * @param \Drupal\drupal_content_sync\Entity\Pool $pool
   *
   * @return \Drupal\drupal_content_sync\Entity\MetaInformation|mixed
   */
  public static function getInfoForEntity($entity_type, $entity_uuid, Flow $flow, Pool $pool) {
    if (!$entity_type) {
      throw new \Exception('$entity_type is required.');
    }
    if (!$entity_uuid) {
      throw new \Exception('$entity_uuid is required.');
    }
    /**
     * @var \Drupal\drupal_content_sync\Entity\MetaInformation[] $entities
     */
    $entities = \Drupal::entityTypeManager()
      ->getStorage('dcs_meta_info')
      ->loadByProperties([
        'entity_type' => $entity_type,
        'entity_uuid' => $entity_uuid,
        'flow' => $flow->id,
        'pool' => $pool->id,
      ]);

    return reset($entities);
  }

  /**
   *
   */
  public static function getLastExportForEntity(FieldableEntityInterface $entity) {
    $meta_infos = MetaInformation::getInfosForEntity($entity->getEntityTypeId(), $entity->uuid());
    $latest = NULL;
    foreach ($meta_infos as $info) {
      if ($info->getLastExport() && (!$latest || $info->getLastExport() > $latest)) {
        $latest = $info->getLastExport();
      }
    }
    return $latest;
  }

  /**
   *
   */
  public static function accessTemporaryExportPoolInfoForField($entity_type, $uuid, $field_name, $delta, $set_flow_id = NULL, $set_pool_ids = NULL, $tree_position = []) {
    static $field_storage = [];

    if ($set_flow_id && $set_pool_ids) {
      $data = [
        'flow_id'   => $set_flow_id,
        'pool_ids'  => $set_pool_ids,
      ];
      if (!isset($field_storage[$entity_type][$uuid])) {
        $field_storage[$entity_type][$uuid] = [];
      }
      $setter = &$field_storage[$entity_type][$uuid];
      foreach ($tree_position as $name) {
        if (!isset($setter[$name])) {
          $setter[$name] = [];
        }
        $setter = &$setter[$name];
      }
      if (!isset($setter[$field_name][$delta])) {
        $setter[$field_name][$delta] = [];
      }
      $setter = &$setter[$field_name][$delta];
      $setter = $data;
    }
    else {
      if (!empty($field_storage[$entity_type][$uuid])) {
        $value = $field_storage[$entity_type][$uuid];
        foreach ($tree_position as $name) {
          if (!isset($value[$name])) {
            return NULL;
          }
          $value = $value[$name];
        }
        return isset($value[$field_name][$delta]) ? $value[$field_name][$delta] : NULL;
      }
    }

    return NULL;
  }

  /**
   *
   */
  public static function saveSelectedExportPoolInfoForField($parent_entity, $parent_field_name, $parent_field_delta, $entity_type, $bundle, $uuid, $tree_position = []) {
    $data = MetaInformation::accessTemporaryExportPoolInfoForField($parent_entity->getEntityTypeId(), $parent_entity->uuid(), $parent_field_name, $parent_field_delta, NULL, NULL, $tree_position);

    // On sites that don't export, this will be NULL.
    if (empty($data['flow_id'])) {
      return;
    }

    $values = $data['pool_ids'];

    $processed = [];
    if (is_array($values)) {
      foreach ($values as $id => $selected) {
        if ($selected && $id !== 'ignore') {
          $processed[] = $id;
        }
      }
    }
    else {
      if ($values !== 'ignore') {
        $processed[] = $values;
      }
    }

    MetaInformation::saveSelectedExportPoolInfo($entity_type, $bundle, $uuid, $data['flow_id'], $processed, $parent_entity, $parent_field_name);
  }

  /**
   *
   */
  public static function saveSelectedExportPoolInfo($entity_type, $bundle, $uuid, $flow_id, $pool_ids, $parent_entity = NULL, $parent_field_name = NULL) {
    $flow = Flow::getAll()[$flow_id];
    $pools = Pool::getAll();

    $entity_type_pools = Pool::getSelectablePools($entity_type, $bundle, $parent_entity, $parent_field_name)[$flow_id]['pools'];
    foreach ($entity_type_pools as $entity_type_pool_id => $config) {
      $pool = $pools[$entity_type_pool_id];
      $meta = MetaInformation::getInfoForEntity($entity_type, $uuid, $flow, $pool);
      if (in_array($entity_type_pool_id, $pool_ids)) {
        if (!$meta) {
          $meta = MetaInformation::create([
            'flow' => $flow->id,
            'pool' => $pool->id,
            'entity_type' => $entity_type,
            'entity_uuid' => $uuid,
            'entity_type_version' => Flow::getEntityTypeVersion($entity_type, $bundle),
            'flags' => 0,
            'source_url' => NULL,
          ]);
        }
        $meta->isExportEnabled(TRUE);
        $meta->save();

        continue;
      }

      if ($meta) {
        $meta->isExportEnabled(FALSE);
        $meta->save();
      }
    }
  }

  /**
   * Get the entity this meta information belongs to.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity() {
    return \Drupal::service('entity.repository')->loadEntityByUuid(
      $this->getEntityTypeName(),
      $this->getUuid()
    );
  }

  /**
   * Returns the information if the entity has originally been created on this
   * site.
   *
   * @param bool $set
   *   Optional parameter to set the value for IsSourceEntity.
   *
   * @return bool
   */
  public function isSourceEntity($set = NULL) {
    if ($set === TRUE) {
      $this->set('flags', $this->get('flags')->value | self::FLAG_IS_SOURCE_ENTITY);
    }
    elseif ($set === FALSE) {
      $this->set('flags', $this->get('flags')->value & ~self::FLAG_IS_SOURCE_ENTITY);
    }
    return (bool) ($this->get('flags')->value & self::FLAG_IS_SOURCE_ENTITY);
  }

  /**
   * Returns the information if the entity has been chosen by the user to
   * be exported with this flow and pool.
   *
   * @param bool $setExportEnabled
   *   Optional parameter to set the value for ExportEnabled.
   * @param bool $setDependencyExportEnabled
   *   Optional parameter to set the value for DependencyExportEnabled.
   *
   * @return bool
   */
  public function isExportEnabled($setExportEnabled = NULL, $setDependencyExportEnabled = NULL) {
    if ($setExportEnabled === TRUE) {
      $this->set('flags', $this->get('flags')->value | self::FLAG_EXPORT_ENABLED);
    }
    elseif ($setExportEnabled === FALSE) {
      $this->set('flags', $this->get('flags')->value & ~self::FLAG_EXPORT_ENABLED);
    }
    if ($setDependencyExportEnabled === TRUE) {
      $this->set('flags', $this->get('flags')->value | self::FLAG_DEPENDENCY_EXPORT_ENABLED);
    }
    elseif ($setDependencyExportEnabled === FALSE) {
      $this->set('flags', $this->get('flags')->value & ~self::FLAG_DEPENDENCY_EXPORT_ENABLED);
    }
    return (bool) ($this->get('flags')->value & (self::FLAG_EXPORT_ENABLED | self::FLAG_DEPENDENCY_EXPORT_ENABLED));
  }

  /**
   * Returns the information if the entity has been chosen by the user to
   * be exported with this flow and pool.
   *
   * @return bool
   */
  public function isManualExportEnabled() {
    return (bool) ($this->get('flags')->value & (self::FLAG_EXPORT_ENABLED));
  }

  /**
   * Returns the information if the entity has been exported with this flow and
   * pool as a dependency.
   *
   * @return bool
   */
  public function isDependencyExportEnabled() {
    return (bool) ($this->get('flags')->value & (self::FLAG_DEPENDENCY_EXPORT_ENABLED));
  }

  /**
   * Returns the information if the user override the entity locally.
   *
   * @param bool $set
   *   Optional parameter to set the value for EditOverride.
   *
   * @return bool
   */
  public function isOverriddenLocally($set = NULL) {
    if ($set === TRUE) {
      $this->set('flags', $this->get('flags')->value | self::FLAG_EDIT_OVERRIDE);
    }
    elseif ($set === FALSE) {
      $this->set('flags', $this->get('flags')->value & ~self::FLAG_EDIT_OVERRIDE);
    }
    return (bool) ($this->get('flags')->value & self::FLAG_EDIT_OVERRIDE);
  }

  /**
   * Returns the information if the user allowed the export.
   *
   * @param bool $set
   *   Optional parameter to set the value for UserAllowedExport.
   *
   * @return bool
   */
  public function didUserAllowExport($set = NULL) {
    if ($set === TRUE) {
      $this->set('flags', $this->get('flags')->value | self::FLAG_USER_ALLOWED_EXPORT);
    }
    elseif ($set === FALSE) {
      $this->set('flags', $this->get('flags')->value & ~self::FLAG_USER_ALLOWED_EXPORT);
    }
    return (bool) ($this->get('flags')->value & self::FLAG_USER_ALLOWED_EXPORT);
  }

  /**
   * Returns the information if the entity is deleted.
   *
   * @param bool $set
   *   Optional parameter to set the value for Deleted.
   *
   * @return bool
   */
  public function isDeleted($set = NULL) {
    if ($set === TRUE) {
      $this->set('flags', $this->get('flags')->value | self::FLAG_DELETED);
    }
    elseif ($set === FALSE) {
      $this->set('flags', $this->get('flags')->value & ~self::FLAG_DELETED);
    }
    return (bool) ($this->get('flags')->value & self::FLAG_DELETED);
  }

  /**
   * Returns the timestamp for the last import.
   *
   * @return int
   */
  public function getLastImport() {
    return $this->get('last_import')->value;
  }

  /**
   * Set the last import timestamp.
   *
   * @param int $timestamp
   */
  public function setLastImport($timestamp) {
    $this->set('last_import', $timestamp);
  }

  /**
   * Returns the UUID of the entity this information belongs to.
   *
   * @return string
   */
  public function getUuid() {
    return $this->get('entity_uuid')->value;
  }

  /**
   * Returns the entity type name of the entity this information belongs to.
   *
   * @return string
   */
  public function getEntityTypeName() {
    return $this->get('entity_type')->value;
  }

  /**
   * Returns the timestamp for the last export.
   *
   * @return int
   */
  public function getLastExport() {
    return $this->get('last_export')->value;
  }

  /**
   * Set the last import timestamp.
   *
   * @param int $timestamp
   */
  public function setLastExport($timestamp) {
    $this->set('last_export', $timestamp);
  }

  /**
   * Get the flow.
   *
   * @return \Drupal\drupal_content_sync\Entity\Flow
   */
  public function getFlow() {
    return Flow::getAll()[$this->get('flow')->value];
  }

  /**
   * Get the pool.
   *
   * @return \Drupal\drupal_content_sync\Entity\Pool
   */
  public function getPool() {
    return Pool::getAll()[$this->get('pool')->value];
  }

  /**
   * Returns the entity type version.
   *
   * @return string
   */
  public function getEntityTypeVersion() {
    return $this->get('entity_type_version')->value;
  }

  /**
   * Set the last import timestamp.
   *
   * @param string $version
   */
  public function setEntityTypeVersion($version) {
    $this->set('entity_type_version', $version);
  }

  /**
   * Returns the entities source url.
   *
   * @return string
   */
  public function getSourceUrl() {
    return $this->get('source_url')->value;
  }

  /**
   * Get a previously saved key=>value pair.
   *
   * @see self::setData()
   *
   * @param string|string[] $key
   *   The key to retrieve.
   *
   * @return mixed Whatever you previously stored here or NULL if the key
   *   doesn't exist.
   */
  public function getData($key) {
    $data    = $this->get('data')->getValue()[0];
    $storage = &$data;

    if (!is_array($key)) {
      $key = [$key];
    }

    foreach ($key as $index) {
      if (!isset($storage[$index])) {
        return NULL;
      }
      $storage = &$storage[$index];
    }

    return $storage;
  }

  /**
   * Set a key=>value pair.
   *
   * @param string|string[] $key
   *   The key to set (for hierarchical usage, provide
   *   an array of indices.
   * @param mixed $value
   *   The value to set. Must be a valid value for Drupal's
   *   "map" storage (so basic types that can be serialized).
   */
  public function setData($key, $value) {
    $data = $this->get('data')->getValue();
    if (!empty($data)) {
      $data = $data[0];
    }
    else {
      $data = [];
    }
    $storage = &$data;

    if (!is_array($key)) {
      $key = [$key];
    }

    foreach ($key as $index) {
      if (!isset($storage[$index])) {
        $storage[$index] = [];
      }
      $storage = &$storage[$index];
    }

    $storage = $value;
    $this->set('data', $data);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['flow'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Flow'))
      ->setDescription(t('The flow the meta entity is based on.'));

    $fields['pool'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Pool'))
      ->setDescription(t('The pool the entity is connected to.'));

    $fields['entity_uuid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity UUID'))
      ->setDescription(t('The UUID of the entity that is synchronized.'))
      ->setSetting('max_length', 128);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The entity type of the entity that is synchronized.'));

    $fields['entity_type_version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type version'))
      ->setDescription(t('The version of the entity type provided by Content Sync.'))
      ->setSetting('max_length', 32);

    $fields['source_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source URL'))
      ->setDescription(t('The entities source URL.'))
      ->setRequired(FALSE);

    $fields['last_export'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last exported'))
      ->setDescription(t('The last time the entity got exported.'))
      ->setRequired(FALSE);

    $fields['last_import'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last import'))
      ->setDescription(t('The last time the entity got imported.'))
      ->setRequired(FALSE);

    $fields['flags'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Flags'))
      ->setDescription(t('Stores boolean information about the exported/imported entity.'))
      ->setSetting('unsigned', TRUE)
      ->setDefaultValue(0);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Data'))
      ->setDescription(t('Stores further information about the exported/imported entity that can also be used by entity and field handlers.'))
      ->setRequired(FALSE);

    return $fields;
  }

}
