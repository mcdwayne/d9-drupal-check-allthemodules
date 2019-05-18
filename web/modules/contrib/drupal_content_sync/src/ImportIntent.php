<?php

namespace Drupal\drupal_content_sync;

use Drupal\drupal_content_sync\Entity\Flow;
use Drupal\drupal_content_sync\Entity\Pool;

/**
 *
 */
class ImportIntent extends SyncIntent {
  /**
   * @var string IMPORT_DISABLED
   *   Disable import completely for this entity type, unless forced.
   *   - used as a configuration option
   *   - not used as $action
   */
  const IMPORT_DISABLED = 'disabled';
  /**
   * @var string IMPORT_AUTOMATICALLY
   *   Automatically import all entities of this entity type.
   *   - used as a configuration option
   *   - used as $action
   */
  const IMPORT_AUTOMATICALLY = 'automatically';
  /**
   * @var string IMPORT_MANUALLY
   *   Import only some of these entities, chosen manually.
   *   - used as a configuration option
   *   - used as $action
   */
  const IMPORT_MANUALLY = 'manually';
  /**
   * @var string IMPORT_AS_DEPENDENCY
   *   Import only some of these entities, imported if other imported entities
   *   use it.
   *   - used as a configuration option
   *   - used as $action
   */
  const IMPORT_AS_DEPENDENCY = 'dependency';
  /**
   * @var string IMPORT_FORCED
   *   Force the entity to be imported (as long as a handler is also selected).
   *   Can be used programmatically for custom workflows.
   *   - not used as a configuration option
   *   - used as $action
   */
  const IMPORT_FORCED = 'forced';


  /**
   * @var string IMPORT_UPDATE_IGNORE
   *   Ignore all incoming updates.
   */
  const IMPORT_UPDATE_IGNORE = 'ignore';
  /**
   * @var string IMPORT_UPDATE_FORCE
   *   Overwrite any local changes on all updates.
   */
  const IMPORT_UPDATE_FORCE = 'force';
  /**
   * @var string IMPORT_UPDATE_FORCE_AND_FORBID_EDITING
   *   Import all changes and forbid local editors to change the content.
   */
  const IMPORT_UPDATE_FORCE_AND_FORBID_EDITING = 'force_and_forbid_editing';
  /**
   * @var stringIMPORT_UPDATE_FORCE_UNLESS_OVERRIDDEN
   *   Import all changes and forbid local editors to change the content unless
   *   they check the "override" checkbox. As long as that is checked, we
   *   ignore any incoming updates in favor of the local changes.
   */
  const IMPORT_UPDATE_FORCE_UNLESS_OVERRIDDEN = 'allow_override';


  protected $mergeChanges;

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
   * @param array $data
   *   The data provided from API Unify for imports.
   *   Format is the same as in ::getData()
   */
  public function __construct(Flow $flow, Pool $pool, $reason, $action, $entity_type, $bundle, $data) {
    parent::__construct($flow, $pool, $reason, $action, $entity_type, $bundle, $data['uuid'], isset($data['url']) ? $data['url'] : '');

    if (!empty($data['embed_entities'])) {
      $this->embedEntities = $data['embed_entities'];
    }
    if (!empty($data['apiu_translation'])) {
      $this->translationFieldValues = $data['apiu_translation'];
    }
    if (!empty($data)) {
      $this->fieldValues = array_diff_key(
        $data,
        [
          'embed_entities' => [],
          'apiu_translation' => [],
          'uuid' => NULL,
          'id' => NULL,
          'bundle' => NULL,
        ]
      );
    }

    $this->mergeChanges = $this->flow->getEntityTypeConfig($this->entityType, $this->bundle)['import_updates'] == ImportIntent::IMPORT_UPDATE_FORCE_UNLESS_OVERRIDDEN &&
      $this->meta->isOverriddenLocally();
  }

  /**
   * @return bool
   */
  public function shouldMergeChanges() {
    return $this->mergeChanges;
  }

  /**
   * Import the provided entity.
   *
   * @throws \Drupal\drupal_content_sync\Exception\SyncException
   *
   * @return bool
   */
  public function execute() {
    $import = $this->pool->getNewestTimestamp($this->entityType, $this->uuid, TRUE);
    if (!$import) {
      if ($this->action == SyncIntent::ACTION_UPDATE) {
        $this->action = SyncIntent::ACTION_CREATE;
      }
    }
    elseif ($this->action == SyncIntent::ACTION_CREATE) {
      $this->action = SyncIntent::ACTION_UPDATE;
    }
    $import = time();

    if ($this->pool->isEntityDeleted($this->entityType, $this->uuid)) {
      return TRUE;
    }

    $config = $this->flow->getEntityTypeConfig($this->entityType, $this->bundle);
    $handler = $this->flow->getEntityTypeHandler($config);

    self::entityHasBeenImportedByRemote($this->entityType, $this->uuid, TRUE);

    $result = $handler->import($this);

    \Drupal::logger('drupal_content_sync')->info('@not IMPORT @action @entity_type:@bundle @uuid @reason: @message', [
      '@reason' => $this->reason,
      '@action' => $this->action,
      '@entity_type'  => $this->entityType,
      '@bundle' => $this->bundle,
      '@uuid' => $this->uuid,
      '@not' => $result ? '' : 'NO',
      '@message' => $result ? t('The entity has been imported.') : t('The entity handler denied to import this entity.'),
    ]);

    // Don't save meta entity if entity wasn't imported anyway.
    if (!$result) {
      return FALSE;
    }

    $this->meta->save();

    $this->pool->setTimestamp($this->entityType, $this->uuid, $import, TRUE);
    if ($this->action == SyncIntent::ACTION_DELETE) {
      $this->pool->markDeleted($this->entityType, $this->uuid);
    }

    return TRUE;
  }

  /**
   * Check if the provided entity has just been imported by API Unify in this
   * very request. In this case it doesn't make sense to perform a remote
   * request telling API Unify it has been created/updated/deleted
   * (it will know as a result of this current request).
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $entity_uuid
   *   The entity UUID.
   * @param bool $set
   *   If TRUE, this entity will be set to have been imported at this request.
   *
   * @return bool
   */
  public static function entityHasBeenImportedByRemote($entity_type = NULL, $entity_uuid = NULL, $set = FALSE) {
    static $entities = [];

    if (!$entity_type) {
      return !empty($entities);
    }

    if ($set) {
      return $entities[$entity_type][$entity_uuid] = TRUE;
    }

    return !empty($entities[$entity_type][$entity_uuid]);
  }

}
