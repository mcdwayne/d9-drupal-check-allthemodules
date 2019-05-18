<?php

namespace Drupal\odoo_api_entity_sync\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\odoo_api_entity_sync\Exception\PluginLogicException;
use Drupal\odoo_api_entity_sync\Exception\RemovalRequestException;
use Drupal\odoo_api_entity_sync\Exception\ServerErrorException;
use Drupal\odoo_api_entity_sync\Exception\SyncExcludedException;
use Drupal\odoo_api_entity_sync\Event\OdooExportEvent;
use Drupal\odoo_api_entity_sync\MappingManagerInterface;
use Drupal\odoo_api_entity_sync\SyncManagerInterface;
use Drupal\odoo_api\OdooApi\ClientInterface;
use fXmlRpc\Exception\FaultException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Base class for Odoo entity sync plugins.
 */
abstract class EntitySyncBase extends PluginBase implements EntitySyncInterface, ContainerFactoryPluginInterface {

  /**
   * Odoo API client.
   *
   * @var \Drupal\odoo_api\OdooApi\ClientInterface
   */
  protected $odoo;

  /**
   * Odoo sync manager.
   *
   * @var \Drupal\odoo_api_entity_sync\SyncManagerInterface
   */
  protected $syncManager;

  /**
   * ID mapper service.
   *
   * @var \Drupal\odoo_api_entity_sync\MappingManagerInterface
   */
  protected $map;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs sync plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\odoo_api\OdooApi\ClientInterface $odoo_api
   *   Odoo API client.
   * @param \Drupal\odoo_api_entity_sync\SyncManagerInterface $sync_manager
   *   Odoo sync manager.
   * @param \Drupal\odoo_api_entity_sync\MappingManagerInterface $map
   *   ID map service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ClientInterface $odoo_api,
    SyncManagerInterface $sync_manager,
    MappingManagerInterface $map,
    EventDispatcherInterface $event_dispatcher
  ) {
    $this->odoo = $odoo_api;
    $this->map = $map;
    $this->syncManager = $sync_manager;
    $this->eventDispatcher = $event_dispatcher;
    return parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('odoo_api.api_client'),
      $container->get('odoo_api_entity_sync.sync'),
      $container->get('odoo_api_entity_sync.mapping'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function assertEntity(EntityInterface $entity) {
    $def = $this->getPluginDefinition();
    if ($entity->getEntityTypeId() != $def['entityType']) {
      throw new \LogicException('Incorrect entity type.');
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function export(EntityInterface $entity) {
    try {
      if ($odoo_id = $this->getOwnOdooId($entity)) {
        $odoo_id = (int) $odoo_id;
        try {
          // Update existing Odoo object.
          $this->odoo->write($this->getOdooModel(), [$odoo_id], $this->getOdooFields($entity));
        }
        catch (FaultException $e) {
          if (!$this->recreateDeleted($entity)
            || strpos($e->getMessage(), 'One of the records you are trying to modify has already been deleted') === FALSE) {
            // Only recreate entity if told so.
            throw $e;
          }
          // Re-create object.
          $odoo_id = $this->odoo->create($this->getOdooModel(), $this->getOdooFields($entity));
        }
        $this->eventDispatcher->dispatch(OdooExportEvent::WRITE, new OdooExportEvent($entity, $this->getOdooModel(), $this->getExportType(), $odoo_id));
      }
      else {
        // Create new Odoo object.
        $odoo_id = $this->odoo->create($this->getOdooModel(), $this->getOdooFields($entity));
        $this->eventDispatcher->dispatch(OdooExportEvent::CREATE, new OdooExportEvent($entity, $this->getOdooModel(), $this->getExportType(), $odoo_id));
      }
      return $odoo_id;
    }
    catch (FaultException $e) {
      throw new ServerErrorException($this->getEntityType(), $this->getOdooModel(), $this->getExportType(), $entity->id(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFromOdoo(EntityInterface $entity) {
    try {
      if (!($odoo_id = $this->getOwnOdooId($entity))) {
        $arguments = [
          '%plugin' => $this->getPluginId(),
        ];
        $message = (string) (new FormattableMarkup('The %plugin delete() method was called but no Odoo object ID found. This is probably a bug.', $arguments));
        throw new PluginLogicException($this->getEntityType(), $this->getOdooModel(), $this->getExportType(), $entity->id(), $message);
      }

      $this->odoo->unlink($this->getOdooModel(), [$odoo_id]);
      $this->eventDispatcher->dispatch(OdooExportEvent::DELETE_REQUEST, new OdooExportEvent($entity, $this->getOdooModel(), $this->getExportType(), $odoo_id));
    }
    catch (FaultException $e) {
      throw new ServerErrorException($this->getEntityType(), $this->getOdooModel(), $this->getExportType(), $entity->id(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function assertShouldSync(EntityInterface $entity) {
    $should_sync = $this->shouldSync($entity);
    $should_delete = $this->shouldDelete($entity);

    if ($should_sync && $should_delete) {
      // Detect buggy plugins. If we don't do that we'd end up with some
      // entities exporting and removing back and forward.
      $arguments = [
        '%plugin' => $this->getPluginId(),
      ];
      $message = (string) (new FormattableMarkup('The %plugin requests both sync and removal at same time. This is a sync plugin bug.', $arguments));
      throw new PluginLogicException($this->getEntityType(), $this->getOdooModel(), $this->getExportType(), $entity->id(), NULL, $message);
    }

    // Removal requested.
    if ($should_delete) {
      if ($this->entityExported($entity)) {
        throw new RemovalRequestException($this->getEntityType(), $this->getOdooModel(), $this->getExportType(), $entity->id());
      }
    }

    // Item shouldn't be synced.
    if (!$should_sync) {
      throw new SyncExcludedException($this->getEntityType(), $this->getOdooModel(), $this->getExportType(), $entity->id());
    }

    // Proceed with sync.
    return $this;
  }

  /**
   * Gets Odoo IDs of referenced entities.
   *
   * This method may lazily export the entity.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $odoo_model
   *   Odoo model name.
   * @param string $export_type
   *   Export type.
   * @param int[] $entity_id
   *   Referencing entity ID.
   *
   * @return int[]
   *   An array keyed by entity id. Value - the odoo id.
   *
   * @throws \Drupal\odoo_api_entity_sync\Plugin\Exception\MissingPluginException
   *   Missing sync plugin.
   * @throws \Drupal\odoo_api_entity_sync\Exception\ExportException
   *   Referencing entity sync exception.
   *
   *   This exception is catched at
   *   \Drupal\odoo_api_entity_sync\SyncManager::sync(), you should NOT catch
   *   it.
   */
  protected function getReferencedEntitiesOdooId($entity_type, $odoo_model, $export_type, array $entity_id) {
    $map = $this->syncManager->export($entity_type, $odoo_model, $export_type, $entity_id);
    array_walk($map, function (&$odoo_id) {
      $odoo_id = (int) $odoo_id;
    });
    return $map;
  }

  /**
   * Gets Odoo ID of referenced entity.
   *
   * This method may lazily export the entity.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $odoo_model
   *   Odoo model name.
   * @param string $export_type
   *   Export type.
   * @param int $entity_id
   *   Referencing entity ID.
   *
   * @return int
   *   Get Odoo ID of referencing entity.
   *
   * @throws \Drupal\odoo_api_entity_sync\Plugin\Exception\MissingPluginException
   *   Missing sync plugin.
   * @throws \Drupal\odoo_api_entity_sync\Exception\ExportException
   *   Referencing entity sync exception.
   *
   *   This exception is catched at
   *   \Drupal\odoo_api_entity_sync\SyncManager::sync(), you should NOT catch
   *   it.
   */
  protected function getReferencedEntityOdooId($entity_type, $odoo_model, $export_type, $entity_id) {
    $entity_id = is_array($entity_id) ? $entity_id : [$entity_id];
    $map = $this->getReferencedEntitiesOdooId($entity_type, $odoo_model, $export_type, $entity_id);
    $entity_id = reset($entity_id);
    return $map[$entity_id];
  }

  /**
   * Get current Odoo model name.
   *
   * @return string
   *   Odoo model name.
   *
   * @deprecated will be removed in odoo_api 8.x-1.0.
   *   Instead, use getOdooModel() method.
   */
  protected function getOdooModelName() {
    return $this->getPluginDefinition()['odooModel'];
  }

  /**
   * Get Odoo ID of the entity exported by this plugin.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @return int|false
   *   Odoo ID or FALSE.
   */
  protected function getOwnOdooId(EntityInterface $entity) {
    $ids = $this
      ->map
      ->getIdMap($this->getEntityType(), $this->getOdooModel(), $this->getExportType(), $entity->id());

    return $ids[$entity->id()];
  }

  /**
   * Get entity type from plugin definition.
   *
   * @return string
   *   Sync plugin entity type.
   */
  protected function getEntityType() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['entityType'];
  }

  /**
   * Get Odoo model from plugin definition.
   *
   * @return string
   *   Sync plugin Odoo model name.
   */
  protected function getOdooModel() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['odooModel'];
  }

  /**
   * Get export type from plugin definition.
   *
   * @return string
   *   Sync plugin export type.
   */
  protected function getExportType() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['exportType'];
  }

  /**
   * Check if the entity is already exported.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @return bool
   *   Whether the entity is exported.
   */
  protected function entityExported(EntityInterface $entity) {
    return (bool) $this->getOwnOdooId($entity);
  }

  /**
   * Check whether an entity deleted at Odoo should be re-created on export.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity being exported.
   *
   * @return bool
   *   Whether this entity should be re-created.
   */
  protected function recreateDeleted(EntityInterface $entity) {
    return FALSE;
  }

  /**
   * Get Odoo fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @return array
   *   Array of Odoo fields for exporting.
   */
  abstract protected function getOdooFields(EntityInterface $entity);

  /**
   * Checks whether the entity can be synced or not.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   Whether the entity can be synced or not.
   */
  abstract protected function shouldSync(EntityInterface $entity);

  /**
   * Checks whether the entity should be removed from Odoo.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   Whether the Odoo object should be removed.
   */
  abstract protected function shouldDelete(EntityInterface $entity);

}
