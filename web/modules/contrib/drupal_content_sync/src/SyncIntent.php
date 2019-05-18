<?php

namespace Drupal\drupal_content_sync;

use Drupal\Core\Entity\EntityInterface;
use Drupal\drupal_content_sync\Entity\Flow;
use Drupal\drupal_content_sync\Entity\MetaInformation;
use Drupal\drupal_content_sync\Entity\Pool;
use Drupal\drupal_content_sync\Exception\SyncException;

/**
 * Class SyncIntent.
 *
 * For every import and export of every entity, an instance of this class is
 * created and passed through the entity and field handlers. When exporting,
 * you can set field values and embed entities. When exporting, you can
 * receive these values back and resolve the entity references you saved.
 *
 * The same class is used for export and import to allow adjusting values
 * with hook integration.
 */
abstract class SyncIntent {
  /**
   * @var \Drupal\drupal_content_sync\Entity\Flow
   *   The synchronization this request spawned at.
   * @var string            $entityType             Entity type of the processed entity.
   * @var string            $bundle                 Bundle of the processed entity.
   * @var string            $uuid                   UUID of the processed entity.
   * @var array             $fieldValues            The field values for the untranslated entity.
   * @var array             $embedEntities          The entities that should be processed along with this entity. Each entry is an array consisting of all SyncIntent::_*KEY entries.
   * @var string            $activeLanguage         The currently active language.
   * @var array             $translationFieldValues The field values for the translation of the entity per language as key.
   */
  protected $flow,
    $entityType,
    $bundle,
    $uuid,
    $fieldValues,
    $embedEntities,
    $activeLanguage,
    $translationFieldValues;

  /**
   * @var \Drupal\drupal_content_sync\Entity\MetaInformation
   */
  protected $meta;
  protected $pool,
    $reason,
    $action,
    $entity;

  /**
   * Keys used in the definition array for embedded entities.
   *
   * @see SyncIntent::embedEntity        for its usage on export.
   * @see SyncIntent::loadEmbeddedEntity for its usage on import.
   *
   * @var string API_KEY                  The API of the processed and referenced entity.
   * @var string ENTITY_TYPE_KEY          The entity type of the referenced entity.
   * @var string BUNDLE_KEY               The bundle of the referenced entity.
   * @var string VERSION_KEY              The version of the entity type of the referenced entity.
   * @var string UUID_KEY                 The UUID of the referenced entity.
   * @var string AUTO_EXPORT_KEY          Whether or not to automatically export the referenced entity as well.
   * @var string SOURCE_CONNECTION_ID_KEY The API Unify connection ID of the referenced entity.
   * @var string POOL_CONNECTION_ID_KEY   The API Unify connection ID of the pool for this api + entity type + bundle.
   */
  const API_KEY                  = 'api';
  const ENTITY_TYPE_KEY          = 'type';
  const BUNDLE_KEY               = 'bundle';
  const VERSION_KEY              = 'version';
  const UUID_KEY                 = 'uuid';
  const AUTO_EXPORT_KEY          = 'auto_export';
  const SOURCE_CONNECTION_ID_KEY = 'connection_id';
  const POOL_CONNECTION_ID_KEY   = 'next_connection_id';

  /**
   * @var string ACTION_CREATE
   *   export/import the creation of this entity.
   */
  const ACTION_CREATE = 'create';
  /**
   * @var string ACTION_UPDATE
   *   export/import the update of this entity.
   */
  const ACTION_UPDATE = 'update';
  /**
   * @var string ACTION_DELETE
   *   export/import the deletion of this entity.
   */
  const ACTION_DELETE = 'delete';
  /**
   * @var string ACTION_DELETE_TRANSLATION
   *   Drupal doesn't update the ->getTranslationStatus($langcode) to
   *   TRANSLATION_REMOVED before calling hook_entity_translation_delete, so we
   *   need to use a custom action to circumvent deletions of translations of
   *   entities not being handled. This is only used for calling the
   *   ->exportEntity function. It will then be replaced by a simple
   *   ::ACTION_UPDATE.
   */
  const ACTION_DELETE_TRANSLATION = 'delete translation';

  /**
   * SyncIntent constructor.
   *
   * @param \Drupal\drupal_content_sync\Entity\Flow $flow
   *   {@see SyncIntent::$sync}.
   * @param \Drupal\drupal_content_sync\Entity\Pool $pool
   *   {@see SyncIntent::$pool}.
   * @param string $reason
   *   {@see Flow::EXPORT_*} or {@see Flow::IMPORT_*}.
   * @param string $action
   *   {@see ::ACTION_*}.
   * @param string $entity_type
   *   {@see SyncIntent::$entityType}.
   * @param string $bundle
   *   {@see SyncIntent::$bundle}.
   * @param string $uuid
   *   {@see SyncIntent::$uuid}.
   * @param string $source_url
   *   The source URL if imported or NULL if exported from this site.
   */
  public function __construct(Flow $flow, Pool $pool, $reason, $action, $entity_type, $bundle, $uuid, $source_url = NULL) {
    $this->flow       = $flow;
    $this->pool       = $pool;
    $this->reason     = $reason;
    $this->action     = $action;
    $this->entityType = $entity_type;
    $this->bundle     = $bundle;
    $this->uuid       = $uuid;
    $this->meta       = MetaInformation::getInfoForEntity($entity_type, $uuid, $flow, $pool);
    if (!$this->meta) {
      $this->meta = MetaInformation::create([
        'flow' => $this->flow->id,
        'pool' => $this->pool->id,
        'entity_type' => $entity_type,
        'entity_uuid' => $uuid,
        'entity_type_version' => Flow::getEntityTypeVersion($entity_type, $bundle),
        'flags' => 0,
        'source_url' => $source_url,
      ]);
      if (!$source_url && $this instanceof ExportIntent) {
        $this->meta->isSourceEntity(TRUE);
      }
    }
    elseif (!$this->meta->getLastExport() && !$this->meta->getLastImport()) {
      if (!$source_url && $this instanceof ExportIntent) {
        $this->meta->isSourceEntity(TRUE);
      }
    }

    $this->embedEntities          = [];
    $this->activeLanguage         = NULL;
    $this->translationFieldValues = NULL;
    $this->fieldValues            = [];
  }

  /**
   * Execute the intent.
   *
   * @return bool
   */
  abstract public function execute();

  /**
   * @return string
   */
  public function getReason() {
    return $this->reason;
  }

  /**
   * @return string
   */
  public function getAction() {
    return $this->action;
  }

  /**
   * @return \Drupal\drupal_content_sync\Entity\Flow
   */
  public function getFlow() {
    return $this->flow;
  }

  /**
   * @return \Drupal\drupal_content_sync\Entity\Pool
   */
  public function getPool() {
    return $this->pool;
  }

  /**
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The entity of the intent, if it already exists locally.
   */
  public function getEntity() {
    if (!$this->entity) {
      $entity = \Drupal::service('entity.repository')
        ->loadEntityByUuid($this->entityType, $this->uuid);
      if ($entity) {
        $this->setEntity($entity);
      }
    }
    return $this->entity;
  }

  /**
   * Set the entity when importing (may not be saved yet then).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity you just created.
   *
   * @throws \Drupal\drupal_content_sync\Exception\SyncException
   */
  public function setEntity(EntityInterface $entity) {
    if ($entity == $this->entity) {
      return $this->entity;
    }
    if ($this->entity) {
      throw new SyncException(SyncException::CODE_INTERNAL_ERROR, NULL, "Attempting to re-set existing entity.");
    }
    /**
     * @var \Drupal\Core\Entity\FieldableEntityInterface $entity
     * @var \Drupal\Core\Entity\TranslatableInterface $entity
     */
    $this->entity = $entity;
    if ($this->entity) {
      if ($this->activeLanguage) {
        $this->entity = $this->entity->getTranslation($this->activeLanguage);
      }
    }
    return $this->entity;
  }

  /**
   * Retrieve a value you stored before via ::setMetaData().
   *
   * @see MetaInformation::getData()
   *
   * @param string|string[] $key
   *   The key to retrieve.
   *
   * @return mixed Whatever you previously stored here.
   */
  public function getMetaData($key) {
    return $this->meta ? $this->meta->getData($key) : NULL;
  }

  /**
   * Store a key=>value pair for later retrieval.
   *
   * @see MetaInformation::setData()
   *
   * @param string|string[] $key
   *   The key to store the data against. Especially
   *   field handlers should use nested keys like ['field','[name]','[key]'].
   * @param mixed $value
   *   Whatever simple value you'd like to store.
   *
   * @return bool
   */
  public function setMetaData($key, $value) {
    if (!$this->meta) {
      return FALSE;
    }
    $this->meta->setData($key, $value);
    return TRUE;
  }

  /**
   * Get all languages for field translations that are currently used.
   */
  public function getTranslationLanguages() {
    return empty($this->translationFieldValues) ? [] : array_keys($this->translationFieldValues);
  }

  /**
   * Change the language used for provided field values. If you want to add a
   * translation of an entity, the same SyncIntent is used. First, you
   * add your fields using self::setField() for the untranslated version.
   * After that you call self::changeTranslationLanguage() with the language
   * identifier for the translation in question. Then you perform all the
   * self::setField() updates for that language and eventually return to the
   * untranslated entity by using self::changeTranslationLanguage() without
   * arguments.
   *
   * @param string $language
   *   The identifier of the language to switch to or NULL to reset.
   */
  public function changeTranslationLanguage($language = NULL) {
    $this->activeLanguage = $language;
    if ($this->entity) {
      if ($language) {
        $this->entity = $this->entity->getTranslation($language);
      }
      else {
        $this->entity = $this->entity->getTranslation($this->fieldValues['langcode'][0]['value']);
      }
    }
  }

  /**
   * Return the language that's currently used.
   *
   * @see SyncIntent::changeTranslationLanguage() for a detailed explanation.
   */
  public function getActiveLanguage() {
    return $this->activeLanguage;
  }

  /**
   * Get the definition for a referenced entity that should be exported /
   * embedded as well.
   *
   * @see SyncIntent::$embedEntities
   *
   * @param string $entity_type
   *   The entity type of the referenced entity.
   * @param string $bundle
   *   The bundle of the referenced entity.
   * @param string $uuid
   *   The UUID of the referenced entity.
   * @param bool $auto_export
   *   Whether the referenced entity should be exported automatically to all
   *   it's pools as well.
   * @param array $details
   *   Additional details you would like to export.
   *
   * @return array The definition to be exported.
   */
  public function getEmbedEntityDefinition($entity_type, $bundle, $uuid, $auto_export = FALSE, $details = NULL) {
    $version = Flow::getEntityTypeVersion($entity_type, $bundle);

    return array_merge([
      self::API_KEY           => $this->pool->id,
      self::ENTITY_TYPE_KEY   => $entity_type,
      self::UUID_KEY          => $uuid,
      self::BUNDLE_KEY        => $bundle,
      self::VERSION_KEY       => $version,
      self::AUTO_EXPORT_KEY   => $auto_export,
      self::SOURCE_CONNECTION_ID_KEY => ApiUnifyFlowExport::getExternalConnectionId(
        $this->pool->id,
        $this->pool->getSiteId(),
        $entity_type,
        $bundle,
        $version
      ),
      self::POOL_CONNECTION_ID_KEY => ApiUnifyFlowExport::getExternalConnectionId(
        $this->pool->id,
        ApiUnifyPoolExport::POOL_SITE_ID,
        $entity_type,
        $bundle,
        $version
      ),
    ], $details ? $details : []);
  }

  /**
   * Embed an entity by its properties.
   *
   * @see SyncIntent::getEmbedEntityDefinition
   * @see SyncIntent::embedEntity
   *
   * @param string $entity_type
   *   {@see SyncIntent::getEmbedEntityDefinition}.
   * @param string $bundle
   *   {@see SyncIntent::getEmbedEntityDefinition}.
   * @param string $uuid
   *   {@see SyncIntent::getEmbedEntityDefinition}.
   * @param bool $auto_export
   *   {@see SyncIntent::getEmbedEntityDefinition}.
   * @param array $details
   *   {@see SyncIntent::getEmbedEntityDefinition}.
   *
   * @return array
   *   The definition you can store via {@see SyncIntent::setField} and on the
   *   other end receive via {@see SyncIntent::getField}.
   *
   * @throws \Drupal\drupal_content_sync\Exception\SyncException
   */
  public function embedEntityDefinition($entity_type, $bundle, $uuid, $auto_export = FALSE, $details = NULL) {
    // Prevent circle references without middle man.
    if ($entity_type == $this->entityType && $uuid == $this->uuid) {
      throw new SyncException(
        SyncException::CODE_INTERNAL_ERROR,
        NULL,
        "Can't circle-reference own entity (" . $entity_type . " " . $uuid . ")."
      );
    }

    // Already included? Just return the definition then.
    foreach ($this->embedEntities as &$definition) {
      if ($definition[self::ENTITY_TYPE_KEY] == $entity_type && $definition[self::UUID_KEY] == $uuid) {
        // Overwrite auto export flag if it should be set now.
        if (!$definition[self::AUTO_EXPORT_KEY] && $auto_export) {
          $definition[self::AUTO_EXPORT_KEY] = TRUE;
        }
        return $this->getEmbedEntityDefinition(
          $entity_type, $bundle, $uuid, $auto_export, $details
        );
      }
    }

    return $this->embedEntities[] = $this->getEmbedEntityDefinition(
      $entity_type, $bundle, $uuid, $auto_export, $details
    );
  }

  /**
   * Export the provided entity along with the processed entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The referenced entity to export as well.
   * @param bool $auto_export
   *   {@see SyncIntent::getEmbedEntityDefinition}.
   * @param array $details
   *   {@see SyncIntent::getEmbedEntityDefinition}.
   *
   * @return array The definition you can store via {@see SyncIntent::setField} and on the other end receive via {@see SyncIntent::getField}.
   *
   * @throws \Drupal\drupal_content_sync\Exception\SyncException
   */
  public function embedEntity($entity, $auto_export = FALSE, $details = NULL) {
    return $this->embedEntityDefinition(
      $entity->getEntityTypeId(),
      $entity->bundle(),
      $entity->uuid(),
      $auto_export,
      $details
    );
  }

  /**
   * Restore an entity that was added via
   * {@see SyncIntent::embedEntityDefinition} or
   * {@see SyncIntent::embedEntity}.
   *
   * @param array $definition
   *   The definition you saved in a field and gotten
   *   back when calling one of the mentioned functions above.
   *
   * @return \Drupal\Core\Entity\EntityInterface The restored entity.
   */
  public function loadEmbeddedEntity($definition) {
    $version = Flow::getEntityTypeVersion(
      $definition[self::ENTITY_TYPE_KEY],
      $definition[self::BUNDLE_KEY]
    );
    if ($version != $definition[self::VERSION_KEY]) {
      \Drupal::logger('drupal_content_sync')->error('Failed to resolve reference to @entity_type:@bundle: Remote version @remote_version doesn\'t match local version @local_version', [
        '@entity_type'  => $definition[self::ENTITY_TYPE_KEY],
        '@bundle' => $definition[self::BUNDLE_KEY],
        '@remote_version' => $definition[self::VERSION_KEY],
        '@local_version' => $version,
      ]);
      return NULL;
    }

    $entity = \Drupal::service('entity.repository')->loadEntityByUuid(
      $definition[self::ENTITY_TYPE_KEY],
      $definition[self::UUID_KEY]
    );

    return $entity;
  }

  /**
   * Get all embedded entity data besides the predefined keys.
   * Images for example have "alt" and "title" in addition to the file reference.
   *
   * @param $definition
   *
   * @return array
   */
  public function getEmbeddedEntityData($definition) {
    return array_filter($definition, function ($key) {
      return !in_array($key, [
        static::API_KEY,
        static::ENTITY_TYPE_KEY,
        static::BUNDLE_KEY,
        static::VERSION_KEY,
        static::UUID_KEY,
        static::AUTO_EXPORT_KEY,
        static::SOURCE_CONNECTION_ID_KEY,
        static::POOL_CONNECTION_ID_KEY,
      ]);
    }, ARRAY_FILTER_USE_KEY);
  }

  /**
   * Get the data that shall be exported to API Unify.
   *
   * @return array The result.
   */
  public function getData() {
    return array_merge($this->fieldValues, [
      'embed_entities'    => $this->embedEntities,
      'uuid'              => $this->uuid,
      'id'                => $this->uuid,
      'apiu_translation'  => $this->translationFieldValues,
    ]);
  }

  /**
   * Provide the value of a field you stored when exporting by using.
   *
   * @see SyncIntent::setField()
   *
   * @param string $name
   *   The name of the field to restore.
   *
   * @return mixed The value you stored for this field.
   */
  public function getField($name) {
    $source = $this->getFieldValues();

    return isset($source[$name]) ? $source[$name] : NULL;
  }

  /**
   * Get all field values at once for the currently active language.
   *
   * @return array All field values for the active language.
   */
  public function getFieldValues() {
    if ($this->activeLanguage) {
      $source = $this->translationFieldValues[$this->activeLanguage];
    }
    else {
      $source = $this->fieldValues;
    }

    return $source;
  }

  /**
   * Set the value of the given field. By default every field handler
   * will have a field available for storage when importing / exporting that
   * accepts all non-associative array-values. Within this array you can
   * use the following types: array, associative array, string, integer, float,
   * boolean, NULL. These values will be JSON encoded when exporting and JSON
   * decoded when importing. They will be saved in a structured database by
   * API Unify in between, so you can't pass any non-array value by default.
   *
   * @param string $name
   *   The name of the field in question.
   * @param mixed $value
   *   The value to store.
   */
  public function setField($name, $value) {
    if ($this->activeLanguage) {
      if ($this->translationFieldValues === NULL) {
        $this->translationFieldValues = [];
      }
      $this->translationFieldValues[$this->activeLanguage][$name] = $value;
      return;
    }

    $this->fieldValues[$name] = $value;
  }

  /**
   * @see SyncIntent::$entityType
   *
   * @return string
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * @see SyncIntent::$bundle
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * @see SyncIntent::$uuid
   */
  public function getUuid() {
    return $this->uuid;
  }

}
