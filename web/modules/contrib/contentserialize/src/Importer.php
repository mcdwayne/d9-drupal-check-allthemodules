<?php

namespace Drupal\contentserialize;

use Drupal\contentserialize\Event\ImportEvents;
use Drupal\contentserialize\Event\ContextEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Imports entities from a source.
 */
class Importer implements ImporterInterface {

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Create an importer.
   */
  public function __construct(
    SerializerInterface $serializer,
    EntityTypeManagerInterface $entity_type_manager,
    EventDispatcherInterface $event_dispatcher
  ) {
    $this->serializer = $serializer;
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Imports entities from their serialized representation.
   *
   * @param \Traversable|\Drupal\contentserialize\SerializedEntity[] $items
   *   An array or generator of SerializedEntity objects keyed by UUID.
   *
   * @return \Drupal\contentserialize\Result
   *
   * @todo Ensure no more than one file has the same UUID
   * @todo Test that memory use isn't proportional to the number of entities
   *   imported.
   * @todo Validate on final save (wherever that might be) and test.
   */
  public function import($items) {
    $start_import_event = new ContextEvent();
    $this->eventDispatcher->dispatch(ImportEvents::START, $start_import_event);
    $context = $start_import_event->context;

    $completed = [];
    $failed = [];
    foreach ($items as $serialized) {
      try {
        $class = $this->entityTypeManager
          ->getStorage($serialized->getEntityTypeId())
          ->getEntityType()
          ->getClass();
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $this->serializer->deserialize($serialized->getSerialized(), $class, $serialized->getFormat(), $context);
        $entity->save();
        $completed[$entity->getEntityTypeId()][$entity->bundle()][] = $entity->uuid();
      }
      catch (\Exception $e) {
        $failed[$serialized->getEntityTypeId()][$serialized->getUuid()] = $e->getMessage();
      }
    }

    $this->eventDispatcher->dispatch(ImportEvents::STOP, new ContextEvent($context));

    return new Result($completed, $failed);
  }

}