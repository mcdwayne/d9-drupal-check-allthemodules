<?php

namespace Drupal\drupal_content_sync;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\drupal_content_sync\Entity\Flow;
use Drupal\drupal_content_sync\Entity\MetaInformation;
use Drupal\drupal_content_sync\Entity\Pool;
use Drupal\drupal_content_sync\Exception\SyncException;

/**
 * Class ExportIntent.
 *
 * @package Drupal\drupal_content_sync
 */
class ExportIntent extends SyncIntent {
  /**
   * @var string EXPORT_DISABLED
   *   Disable export completely for this entity type, unless forced.
   *   - used as a configuration option
   *   - not used as $action
   */
  const EXPORT_DISABLED = 'disabled';
  /**
   * @var string EXPORT_AUTOMATICALLY
   *   Automatically export all entities of this entity type.
   *   - used as a configuration option
   *   - used as $action
   */
  const EXPORT_AUTOMATICALLY = 'automatically';
  /**
   * @var string EXPORT_MANUALLY
   *   Export only some of these entities, chosen manually.
   *   - used as a configuration option
   *   - used as $action
   */
  const EXPORT_MANUALLY = 'manually';
  /**
   * @var string EXPORT_AS_DEPENDENCY
   *   Export only some of these entities, exported if other exported entities
   *   use it.
   *   - used as a configuration option
   *   - used as $action
   */
  const EXPORT_AS_DEPENDENCY = 'dependency';
  /**
   * @var string EXPORT_FORCED
   *   Force the entity to be exported (as long as a handler is also selected).
   *   Can be used programmatically for custom workflows.
   *   - not used as a configuration option
   *   - used as $action
   */
  const EXPORT_FORCED = 'forced';
  /**
   * @var string EXPORT_ANY
   *   Only used as a filter to check if the Flow exports this entity in any
   *   way.
   */
  const EXPORT_ANY = 'any';

  /**
   * ExportIntent constructor.
   *
   * @param \Drupal\drupal_content_sync\Entity\Flow $flow
   * @param \Drupal\drupal_content_sync\Entity\Pool $pool
   * @param $reason
   * @param $action
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function __construct(Flow $flow, Pool $pool, $reason, $action, EntityInterface $entity) {
    parent::__construct($flow, $pool, $reason, $action, $entity->getEntityTypeId(), $entity->bundle(), $entity->uuid());
    $this->entity = $entity;
  }

  /**
   * Serialize the given entity using the entity export and field export
   * handlers.
   *
   * @param array &$result
   *   The data to be provided to API Unify.
   * @param \Drupal\drupal_content_sync\ExportIntent $intent
   *
   * @throws \Drupal\drupal_content_sync\Exception\SyncException
   *
   * @return bool
   *   Whether or not the export could be gotten.
   */
  public function serialize(array &$result) {
    $config = $this->flow->getEntityTypeConfig($this->entityType, $this->bundle);
    $handler = $this->flow->getEntityTypeHandler($config);

    $status = $handler->export($this);

    if (!$status) {
      return FALSE;
    }

    $result = $this->getData();
    return TRUE;
  }

  /**
   * Wrapper for {@see Flow::getExternalConnectionPath}.
   *
   * @param string $entity_type_name
   * @param string $bundle_name
   * @param string $entity_uuid
   *
   * @return string
   */
  public function getExternalUrl($uuid) {
    $url = $this->pool->getBackendUrl() . '/' . ApiUnifyFlowExport::getExternalConnectionPath(
        $this->pool->id,
        $this->pool->getSiteId(),
        $this->entityType,
        $this->bundle,
        $this->flow->sync_entities[$this->entityType . '-' . $this->bundle]['version']
      );

    if ($uuid) {
      $url .= '/' . $uuid;
    }

    return $url;
  }

  /**
   * Export the given entity.
   *
   * @param ExportIntent $intent
   *   The data required to perform the export.
   *
   * @throws \Drupal\drupal_content_sync\Exception\SyncException
   *
   * @return bool Whether or not the entity has actually been exported.
   */
  public function execute() {
    $action = $this->getAction();
    $reason = $this->getReason();
    $entity = $this->getEntity();

    /**
     * @var array $deletedTranslations
     *   The translations that have been deleted. Important to notice when
     *   updates must be performed (see ::ACTION_DELETE_TRANSLATION).
     */
    static $deletedTranslations = [];

    if ($action == SyncIntent::ACTION_DELETE_TRANSLATION) {
      $deletedTranslations[$entity->getEntityTypeId()][$entity->uuid()] = TRUE;
      return FALSE;
    }

    if ($entity instanceof TranslatableInterface) {
      $entity = $entity->getUntranslated();
      $this->entity = $entity;
    }
    $export = time();
    if ($entity instanceof EntityChangedInterface) {
      $export = $entity->getChangedTime();
      if ($entity instanceof TranslatableInterface) {
        foreach ($entity->getTranslationLanguages(FALSE) as $language) {
          $translation = $entity->getTranslation($language->getId());
          /**
           * @var \Drupal\Core\Entity\EntityChangedInterface $translation
           */
          if ($translation->getChangedTime() > $export) {
            $export = $translation->getChangedTime();
          }
        }
      }
    }

    // If this very request was sent to delete/create this entity, ignore the
    // export as the result of this request will already tell API Unify it has
    // been deleted. Otherwise API Unify will return a reasonable 404 for
    // deletions.
    if (ImportIntent::entityHasBeenImportedByRemote($entity->getEntityTypeId(), $entity->uuid())) {
      return FALSE;
    }

    $entity_type   = $entity->getEntityTypeId();
    $entity_bundle = $entity->bundle();
    $entity_uuid   = $entity->uuid();

    $exported = $this->meta->getLastExport();

    if ($exported) {
      if ($action == SyncIntent::ACTION_CREATE) {
        $action = SyncIntent::ACTION_UPDATE;
      }
    }
    else {
      if ($action == SyncIntent::ACTION_UPDATE) {
        $action = SyncIntent::ACTION_CREATE;
      }
      // If the entity was deleted but has never been exported before,
      // exporting the deletion action doesn't make sense as it doesn't even
      // exist remotely.
      elseif ($action == SyncIntent::ACTION_DELETE) {
        return FALSE;
      }
    }

    $dcs_disable_optimization = boolval(\Drupal::config('drupal_content_sync.debug')
      ->get('dcs_disable_optimization'));

    // If the entity didn't change, it doesn't have to be re-exported.
    if (!$dcs_disable_optimization && $this->meta->getLastExport() && $this->meta->getLastExport() >= $export && $reason != self::EXPORT_FORCED &&
      $action != SyncIntent::ACTION_DELETE &&
      empty($deletedTranslations[$entity->getEntityTypeId()][$entity->uuid()])) {
      // Don't use optimization for taxonomy terms as Drupal doesn't update the
      // changed timestamp on the entity when moving it in the tree for the
      // first time.
      if ($entity_type != 'taxonomy_term') {
        return FALSE;
      }
    }

    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');

    $proceed = TRUE;

    if (!self::$exported) {
      self::$exported = [];
    }
    if (isset(self::$exported[$action][$entity_type][$entity_bundle][$entity_uuid][$this->pool->id])) {
      return FALSE;
    }
    self::$exported[$action][$entity_type][$entity_bundle][$entity_uuid][$this->pool->id] = TRUE;

    $body = NULL;

    if ($action != SyncIntent::ACTION_DELETE) {
      $body    = [];
      $proceed = $this->serialize($body);

      if ($proceed) {
        $embedded_entities = [];
        if (!empty($body['embed_entities'])) {
          foreach ($body['embed_entities'] as $data) {
            try {
              /**
               * @var \Drupal\Core\Entity\FieldableEntityInterface $embed_entity
               */
              $embed_entity = $entity_repository->loadEntityByUuid($data[SyncIntent::ENTITY_TYPE_KEY], $data[SyncIntent::UUID_KEY]);
              $all_pools    = Pool::getAll();
              $pools        = $this->flow->getUsedExportPools($entity, $this->getReason(), $this->getAction(), TRUE);
              $flows        = Flow::getAll();
              $version      = Flow::getEntityTypeVersion($embed_entity->getEntityTypeId(), $embed_entity->bundle());

              foreach ($flows as $flow) {
                if (!$flow->canExportEntity($embed_entity, self::EXPORT_AS_DEPENDENCY, SyncIntent::ACTION_CREATE) &&
                  !$flow->canExportEntity($embed_entity, self::EXPORT_AUTOMATICALLY, SyncIntent::ACTION_CREATE)) {
                  continue;
                }

                foreach ($flow->getEntityTypeConfig($embed_entity->getEntityTypeId(), $embed_entity->bundle())['export_pools'] as $pool_id => $behavior) {
                  if ($behavior == Pool::POOL_USAGE_FORBID) {
                    continue;
                  }

                  // If this entity was newly created, it won't have any export groups
                  // selected, unless they're FORCED. In this case we add default sync
                  // groups based on the parent entity, as you would expect.
                  if ($data[SyncIntent::AUTO_EXPORT_KEY]) {
                    if (!isset($pools[$pool_id])) {
                      // TODO: Save all parent > child relationships so we can check if this pool is used somewhere else
                      // $pool = $all_pools[$pool_id];
                      // $info = MetaInformation::getInfoForEntity($embed_entity->getEntityTypeId(), $embed_entity->uuid(), $flow, $pool);
                      // if ($info) {
                      //  $info->isExportEnabled(NULL, FALSE);
                      //  $info->save();
                      // }
                      continue;
                    }

                    $pool = $pools[$pool_id];
                    $info = MetaInformation::getInfoForEntity($embed_entity->getEntityTypeId(), $embed_entity->uuid(), $flow, $pool);

                    if (!$info) {
                      $info = MetaInformation::create([
                        'flow' => $flow->id,
                        'pool' => $pool->id,
                        'entity_type' => $embed_entity->getEntityTypeId(),
                        'entity_uuid' => $embed_entity->uuid(),
                        'entity_type_version' => $version,
                        'flags' => 0,
                      ]);
                    }

                    $info->isExportEnabled(NULL, TRUE);
                    $info->save();
                  }
                  else {
                    $pool = $all_pools[$pool_id];
                    if ($behavior == Pool::POOL_USAGE_ALLOW) {
                      $info = MetaInformation::getInfoForEntity($embed_entity->getEntityTypeId(), $embed_entity->uuid(), $flow, $pool);
                      if (!$info || !$info->isExportEnabled()) {
                        continue;
                      }
                    }
                  }

                  ExportIntent::exportEntity($embed_entity, self::EXPORT_AS_DEPENDENCY, SyncIntent::ACTION_CREATE, $flow, $pool);

                  $info = MetaInformation::getInfoForEntity($embed_entity->getEntityTypeId(), $embed_entity->uuid(), $flow, $pool);
                  if (!$info->getLastExport()) {
                    continue;
                  }

                  $definition = $data;
                  $definition[SyncIntent::API_KEY] = $pool->id;
                  $definition[SyncIntent::SOURCE_CONNECTION_ID_KEY] = ApiUnifyFlowExport::getExternalConnectionId(
                    $pool->id,
                    $pool->getSiteId(),
                    $embed_entity->getEntityTypeId(),
                    $embed_entity->bundle(),
                    $version
                  );
                  $definition[SyncIntent::POOL_CONNECTION_ID_KEY] = ApiUnifyFlowExport::getExternalConnectionId(
                    $pool->id,
                    ApiUnifyPoolExport::POOL_SITE_ID,
                    $embed_entity->getEntityTypeId(),
                    $embed_entity->bundle(),
                    $version
                  );
                  $embedded_entities[] = $definition;
                }
              }
            }
            catch (\Exception $e) {
              throw new SyncException(SyncException::CODE_UNEXPECTED_EXCEPTION, $e);
            }
          }
        }

        $body['embed_entities'] = $embedded_entities;
      }
    }

    \Drupal::logger('drupal_content_sync')->info('@not EXPORT @action @entity_type:@bundle @uuid @reason: @message', [
      '@reason' => $reason,
      '@action' => $action,
      '@entity_type'  => $entity_type,
      '@bundle' => $entity_bundle,
      '@uuid' => $entity_uuid,
      '@not' => $proceed ? '' : 'NO',
      '@message' => $proceed ? t('The entity has been exported.') : t('The entity handler denied to export this entity.'),
    ]);

    // Handler chose to deliberately ignore this entity,
    // e.g. a node that wasn't published yet and is not exported unpublished.
    if (!$proceed) {
      return FALSE;
    }

    $url = $this->getExternalUrl($action == SyncIntent::ACTION_CREATE ? NULL : $entity_uuid);

    $headers = [
      'Content-Type' => 'application/json',
    ];

    $methods = [
      SyncIntent::ACTION_CREATE => 'post',
      SyncIntent::ACTION_UPDATE => 'put',
      SyncIntent::ACTION_DELETE => 'delete',
    ];

    try {
      $client = \Drupal::httpClient();
      $response = $client->request(
        $methods[$action],
        $url,
        array_merge(['headers' => $headers], $body ? ['body' => json_encode($body)] : [])
      );
    }
    catch (\Exception $e) {
      \Drupal::logger('drupal_content_sync')->error(
        'Failed to export entity @entity_type-@entity_bundle @entity_uuid to @url' . PHP_EOL . '@message',
        [
          '@entity_type' => $entity_type,
          '@entity_bundle' => $entity_bundle,
          '@entity_uuid' => $entity_uuid,
          '@message' => $e->getMessage(),
          '@url' => $url,
        ]
      );
      throw new SyncException(SyncException::CODE_EXPORT_REQUEST_FAILED, $e);
    }

    if ($response->getStatusCode() != 200 && $response->getStatusCode() != 201) {
      \Drupal::logger('drupal_content_sync')->error(
        'Failed to export entity @entity_type-@entity_bundle @entity_uuid to @url' . PHP_EOL . 'Got status code @status_code @reason_phrase with body:' . PHP_EOL . '@body',
        [
          '@entity_type' => $entity_type,
          '@entity_bundle' => $entity_bundle,
          '@entity_uuid' => $entity_uuid,
          '@status_code' => $response->getStatusCode(),
          '@reason_phrase' => $response->getReasonPhrase(),
          '@message' => $response->getBody() . '',
          '@url' => $url,
        ]
      );
      throw new SyncException(SyncException::CODE_EXPORT_REQUEST_FAILED);
    }

    if (!$this->meta->getLastExport() && !$this->meta->getLastImport() && isset($body['url'])) {
      $this->meta->set('source_url', $body['url']);
    }
    $this->meta->setLastExport($export);

    if ($action == SyncIntent::ACTION_DELETE) {
      $this->meta->isDeleted(TRUE);
      $this->pool->markDeleted($entity_type, $entity_uuid);
    }

    $this->meta->save();

    return TRUE;
  }

  /**
   * @var array
   *   A list of all exported entities to make sure entities aren't exported
   *   multiple times during the same request in the format
   *   [$action][$entity_type][$bundle][$uuid] => TRUE
   */
  static protected $exported;

  /**
   * Check whether the given entity is currently being exported. Useful to check
   * against hierarchical references as for nodes and menu items for example.
   *
   * @param string $entity_type
   *   The entity type to check for.
   * @param string $uuid
   *   The UUID of the entity in question.
   * @param string $pool
   *   The pool to export to.
   * @param null|string $action
   *   See ::ACTION_*.
   *
   * @return bool
   */
  public static function isExporting($entity_type, $uuid, $pool, $action = NULL) {
    foreach (self::$exported as $do => $types) {
      if ($action ? $do != $action : $do == SyncIntent::ACTION_DELETE) {
        continue;
      }
      if (!isset($types[$entity_type])) {
        continue;
      }
      foreach ($types[$entity_type] as $bundle => $entities) {
        if (!empty($entities[$uuid][$pool])) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Helper function to export an entity and throw errors if anything fails.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to export.
   * @param string $reason
   *   {@see Flow::EXPORT_*}.
   * @param string $action
   *   {@see ::ACTION_*}.
   * @param \Drupal\drupal_content_sync\Entity\Flow $flow
   *   The flow to be used. If none is given, all flows that may export this
   *   entity will be asked to do so for all relevant pools.
   * @param \Drupal\drupal_content_sync\Entity\Pool $pool
   *   The pool to be used. If not set, all relevant pools for the flow will be
   *   used one after another.
   *
   * @return bool Whether the entity is configured to be exported or not.
   *
   * @throws \Drupal\drupal_content_sync\Exception\SyncException
   */
  public static function exportEntity(EntityInterface $entity, $reason, $action, Flow $flow = NULL, Pool $pool = NULL) {
    if (!$flow) {
      $flows = Flow::getFlowsForEntity($entity, $reason, $action);
      if (!count($flows)) {
        return FALSE;
      }

      $result = FALSE;
      foreach ($flows as $flow) {
        $result |= self::exportEntity($entity, $reason, $action, $flow);
      }
      return $result;
    }

    if (!$pool) {
      $pools = $flow->getUsedExportPools($entity, $reason, $action, TRUE);
      $result = FALSE;
      foreach ($pools as $pool) {
        $result |= self::exportEntity($entity, $reason, $action, $flow, $pool);
      }
      return $result;
    }

    $intent = new ExportIntent($flow, $pool, $reason, $action, $entity);
    $status = $intent->execute();

    // drupal_set_message($action.' '.$entity->getEntityTypeId().' '.$entity->uuid().' with '.$flow->id.' to '.$pool->id.' as '.$reason.': '.($status?'SUCCESS':'FAILURE'));.
    if ($status) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Helper function to export an entity and display the user the results. If
   * you want to make changes programmatically, use ::exportEntity() instead.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to export.
   * @param string $reason
   *   {@see Flow::EXPORT_*}.
   * @param string $action
   *   {@see ::ACTION_*}.
   * @param \Drupal\drupal_content_sync\Entity\Flow $flow
   *   The flow to be used. If none is given, all flows that may export this
   *   entity will be asked to do so for all relevant pools.
   * @param \Drupal\drupal_content_sync\Entity\Pool $pool
   *   The pool to be used. If not set, all relevant pools for the flow will be
   *   used one after another.
   *
   * @return bool Whether the entity is configured to be exported or not.
   */
  public static function exportEntityFromUi(EntityInterface $entity, $reason, $action, Flow $flow = NULL, Pool $pool = NULL) {
    $messenger = \Drupal::messenger();
    try {
      $status = self::exportEntity($entity, $reason, $action, $flow, $pool);

      if ($status) {
        if ($action == SyncIntent::ACTION_DELETE) {
          $messenger->addMessage(t('%label has been exported with Drupal Content Sync.', ['%label' => $entity->getEntityTypeId()]));
        }
        else {
          $messenger->addMessage(t('%label has been exported with Drupal Content Sync.', ['%label' => $entity->label()]));
        }
        return TRUE;
      }
      return FALSE;
    }
    catch (SyncException $e) {
      $message = $e->parentException ? $e->parentException->getMessage() : (
        $e->errorCode == $e->getMessage() ? '' : $e->getMessage()
      );
      if ($message) {
        $messenger->addWarning(t('Failed to export %label with Drupal Content Sync (%code). Message: %message', [
          '%label' => $entity->label(),
          '%code' => $e->errorCode,
          '%message' => $message,
        ]));
      }
      else {
        $messenger->addWarning(t('Failed to export %label with Drupal Content Sync (%code).', [
          '%label' => $entity->label(),
          '%code' => $e->errorCode,
        ]));
      }
      return TRUE;
    }
  }

}
