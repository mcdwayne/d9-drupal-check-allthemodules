<?php

namespace Drupal\acquia_contenthub\EventSubscriber\ImportFailure;

use Acquia\ContentHubClient\CDFDocument;
use Drupal\acquia_contenthub\AcquiaContentHubEvents;
use Drupal\acquia_contenthub\EntityCdfSerializer;
use Drupal\acquia_contenthub\Event\FailedImportEvent;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\depcalc\DependentEntityWrapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CreateStubs implements EventSubscriberInterface {

  /**
   * The processed dependency count to prevent infinite loops.
   *
   * @var int
   */
  protected static $count = 0;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $manager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $repository;

  /**
   * The entity cdf serializer.
   *
   * @var \Drupal\acquia_contenthub\EntityCdfSerializer
   */
  protected $cdfSerializer;

  /**
   * CreateStubs constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $repository
   *   The entity repository.
   * @param \Drupal\acquia_contenthub\EntityCdfSerializer $cdf_serializer
   *   The cdf serializer.
   */
  public function __construct(EntityTypeManagerInterface $manager, EntityRepositoryInterface $repository, EntityCdfSerializer $cdf_serializer) {
    $this->manager = $manager;
    $this->repository = $repository;
    $this->cdfSerializer = $cdf_serializer;
  }


  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AcquiaContentHubEvents::IMPORT_FAILURE][] = ['onImportFailure', 100];
    return $events;
  }

  /**
   * Generate stub entities for all remaining content entities and reimports.
   *
   * @param \Drupal\acquia_contenthub\Event\FailedImportEvent $event
   *   The failure event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onImportFailure(FailedImportEvent $event) {
    if (static::$count === $event->getCount()) {
      $exception = new \Exception("Potential infinite recursion call interrupted in CreateStubs event subscriber.");
      $event->setException($exception);
      return;
    }
    static::$count = $event->getCount();
    $unprocessed = array_diff(array_keys($event->getCdf()->getEntities()), array_keys($event->getStack()->getDependencies()));
    if (!$unprocessed) {
      $event->stopPropagation();
      return;
    }
    $cdfs = [];
    // Process config entities first.
    foreach ($unprocessed as $key => $uuid) {
      $cdf = $event->getCdf()->getCdfEntity($uuid);
      if ($cdf->getType() === 'drupal8_config_entity') {
        unset($unprocessed[$key]);
        $cdfs[] = $cdf;
        continue;
      }
    }
    // Process content entities and create stubs where necessary.
    foreach ($unprocessed as $key => $uuid) {
      $cdf = $event->getCdf()->getCdfEntity($uuid);
      // This only works on content entities.
      if ($cdf->getType() !== 'drupal8_content_entity') {
        $cdfs[] = $cdf;
        continue;
      }
      $entity_type = $cdf->getAttribute('entity_type')->getValue()['und'];
      $entity = $this->repository->loadEntityByUuid($entity_type, $uuid);
      // No entity loaded, so create a stub to populate with data later.
      if (!$entity) {
        $definition = $this->manager->getDefinition($entity_type);
        $storage = $this->manager->getStorage($entity_type);
        $keys = $definition->getKeys();
        $values = [
          $keys['uuid'] => $uuid,
        ];
        if (!empty($keys['label'])) {
          $values[$keys['label']] = $cdf->getAttribute('label')->getValue()['und'];
        }
        if (!empty($keys['bundle'])) {
          $values[$keys['bundle']] = $cdf->getAttribute('bundle')->getValue()['und'];
        }
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $storage->create($values);
        /**
         * @var string $field_name
         * @var \Drupal\Core\Field\FieldItemListInterface $field
         */
        foreach ($entity as $field_name => $field) {
          if ($entity->getEntityType()->getKey('id') === $field_name) {
            continue;
          }
          if ($field->isEmpty() && $this->fieldIsRequired($field)) {
            $field->generateSampleItems();
          }
        }
        $entity->save();
      }
      $wrapper = new DependentEntityWrapper($entity, TRUE);
      $wrapper->setRemoteUuid($uuid);
      $event->getStack()->addDependency($wrapper);
      $cdfs[] = $cdf;
    }
    $document = new CDFDocument(...$cdfs);
    try {
      $this->cdfSerializer->unserializeEntities($document, $event->getStack());
      $event->stopPropagation();
    }
    catch (\Exception $e) {
      $event->setException($e);
    }
    static::$count = 0;
  }

  /**
   * Determines if a field or field property is required for the entity.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The field to evaluate.
   *
   * @return bool
   */
  protected function fieldIsRequired(FieldItemListInterface $field) : bool {
    if ($field->getFieldDefinition()->isRequired()) {
      return TRUE;
    }
    // Check each field property for its own requirement settings.
    foreach ($field->getFieldDefinition()->getFieldStorageDefinition()->getPropertyDefinitions() as $propertyDefinition) {
      if ($propertyDefinition->isRequired()) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
