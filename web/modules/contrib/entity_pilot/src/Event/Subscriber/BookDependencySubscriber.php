<?php

namespace Drupal\entity_pilot\Event\Subscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_pilot\Event\CalculateDependenciesEvent;
use Drupal\entity_pilot\Event\EntityPilotEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts to the calculate dependencies event.
 */
class BookDependencySubscriber implements EventSubscriberInterface {

  /**
   * Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new BookDependencySubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Adds dependencies for book data.
   *
   * @param \Drupal\entity_pilot\Event\CalculateDependenciesEvent $event
   *   Calculate dependencies event.
   */
  public function calculateBookDependencies(CalculateDependenciesEvent $event) {
    $entity = $event->getEntity();
    if ($entity->getEntityTypeId() !== 'node' || !isset($entity->book)) {
      return;
    }
    $node_storage = $this->entityTypeManager->getStorage('node');
    $dependencies = $event->getDependencies();
    $tags = $event->getTags();
    foreach (['pid', 'bid'] as $field) {
      if (isset($entity->book[$field])) {
        $nid = $entity->book[$field];
        if ($dependant = $node_storage->load($nid)) {
          if (!isset($dependencies[$dependant->uuid()]) && $dependant->uuid() !== $entity->uuid()) {
            $dependencies[$dependant->uuid()] = $dependant;
            $tags[] = sprintf('ep__%s__%s', $dependant->getEntityTypeId(), $dependant->id());
            $child_event = new CalculateDependenciesEvent($dependant, $dependencies, $tags);
            $this->calculateBookDependencies($child_event);
            $dependencies += $child_event->getDependencies();
            $tags = array_unique(array_merge($tags, $child_event->getTags()));
            unset($dependencies[$entity->uuid()]);
          }
        }
      }
    }
    $event->setDependencies($dependencies);
    $event->setTags($tags);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      EntityPilotEvents::CALCULATE_DEPENDENCIES => 'calculateBookDependencies',
    ];
  }

}
