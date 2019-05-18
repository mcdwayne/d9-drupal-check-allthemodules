<?php

namespace Drupal\content_synchronizer\Plugin\content_synchronizer\type_processor;

use Drupal\content_synchronizer\Events\ImportEvent;
use Drupal\content_synchronizer\Processors\Entity\EntityProcessorPluginManager;
use Drupal\content_synchronizer\Processors\ImportProcessor;
use Drupal\content_synchronizer\Service\GlobalReferenceManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\content_synchronizer\Processors\Type\TypeProcessorBase;

/**
 * Plugin implementation For the type processor .
 *
 * @TypeProcessor(
 *   id = "content_synchronizer_entity_reference_field_item_list_type_processor",
 *   fieldType = "Drupal\Core\Field\EntityReferenceFieldItemList"
 * )
 */
class EntityReferenceFieldItemListProcessor extends TypeProcessorBase {

  static protected $dependenciesBuffer = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Listen import event.
    /** @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $dispatcher */
    $dispatcher = \Drupal::service('event_dispatcher');
    $dispatcher->addListener(ImportEvent::ON_ENTITY_IMPORTER, [$this, 'onImportedEntity']);
  }

  /**
   * Return export data array.
   *
   * @param \Drupal\Core\TypedData\TypedData $propertyData
   *   The propertyData.
   *
   * @return array
   *   export data.
   */
  public function getExportedData(TypedData $propertyData) {
    $data = [];

    // Init processor service.
    /** @var \Drupal\content_synchronizer\Processors\Entity\EntityProcessorPluginManager $entityProcessorManager */
    $entityProcessorManager = \Drupal::service(EntityProcessorPluginManager::SERVICE_NAME);

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $order = 0;
    foreach ($propertyData->referencedEntities() as $entity) {
      /** @var \Drupal\content_synchronizer\Processors\Entity\EntityProcessorBase $plugin */
      $plugin = $entityProcessorManager->getInstanceByEntityType($entity->getEntityTypeId());
      if (get_class($entity) != "Drupal\user\Entity\User") {
        if ($gid = $plugin->export($entity)) {
          $data[$order] = $gid;
          $order++;
        }
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function initImportedEntity(EntityInterface $entityToImport, $propertyId, array $data) {
    /** @var \Drupal\content_synchronizer\Service\GlobalReferenceManager $referenceManager */
    $referenceManager = \Drupal::service(GlobalReferenceManager::SERVICE_NAME);

    /** @var \Drupal\content_synchronizer\Processors\ImportProcessor $importProcessor */
    $importProcessor = ImportProcessor::getCurrentImportProcessor();

    /** @var \Drupal\content_synchronizer\Entity\ImportEntity $import */
    $import = $importProcessor->getImport();

    /** @var \Drupal\content_synchronizer\Processors\Entity\EntityProcessorPluginManager $pluginManager */
    $pluginManager = \Drupal::service(EntityProcessorPluginManager::SERVICE_NAME);

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $referenceField */
    $referenceField = $entityToImport->get($propertyId);

    // Parse list of entities :
    if (array_key_exists($propertyId, $data) && is_array($data[$propertyId])) {

      // Empty previous references.
      while ($referenceField->count() > 0) {
        $referenceField->removeItem(0);
      }

      foreach ($data[$propertyId] as $order => $entityGid) {

        // If the entity to reference is currently importing, then we cannot add it to the reference because it probably do not have an id yet.
        if ($import->gidIsCurrentlyImporting($entityGid)) {
          $referenceField->appendItem(NULL);
          $this->addDependencie($entityGid, $referenceField, $order);
        }
        // The entity has already been imported, so we add it to the field.
        elseif ($import->gidHasAlreadyBeenImported($entityGid)) {
          $referenceField->appendItem($referenceManager->getEntityByGid($entityGid));
        }
        // The entity has not been imported yet, so we iport it.
        else {
          // Get the plugin of the entity :
          /** @var \Drupal\content_synchronizer\Processors\Entity\EntityProcessorBase $plugin */
          $plugin = $pluginManager->getInstanceByEntityType($referenceManager->getEntityTypeFromGid($entityGid));
          if ($entityData = $import->getEntityDataFromGid($entityGid)) {
            $referencedEntity = $plugin->import($entityData);
            $referenceField->appendItem($referencedEntity);

          }
        }
      }
    }
  }

  /**
   * Add dependencies to importing data.
   *
   * @param string $gid
   *   The gid.
   * @param \Drupal\Core\Field\EntityReferenceFieldItemList $field
   *   The field.
   * @param int $order
   *   The order.
   */
  public function addDependencie($gid, EntityReferenceFieldItemList $field, $order) {
    self::$dependenciesBuffer[$gid][] = [
      'field' => $field,
      'order' => $order,
    ];
  }

  /**
   * Action on Entity import end.
   *
   * @param \Drupal\content_synchronizer\Events\ImportEvent $event
   *   The event.
   */
  public function onImportedEntity(ImportEvent $event) {
    $gid = $event->getGid();
    $entity = $event->getEntity();
    if (array_key_exists($gid, self::$dependenciesBuffer)) {
      foreach (self::$dependenciesBuffer[$gid] as $parent) {
        $parent['field'][$parent['order']] = $entity;
      }
    }
  }

}
