<?php

namespace Drupal\entity_staging\EventSubscriber;

use Drupal\entity_staging\Event\EntityStagingBeforeExportEvent;
use Drupal\entity_staging\Event\EntityStagingEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribe to EntityStagingEvents::BEFORE_EXPORT events.
 *
 * Perform action before export taxonomy term entities.
 */
class EntityStagingExportTaxonomyTermSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityStagingExportTaxonomyTermSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The content staging manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[EntityStagingEvents::BEFORE_EXPORT][] = ['exportTaxonomyTerm', -10];

    return $events;
  }

  /**
   * Export the taxonomy term's parent.
   *
   * @param \Drupal\entity_staging\Event\EntityStagingBeforeExportEvent $event
   *   The event.
   */
  public function exportTaxonomyTerm(EntityStagingBeforeExportEvent $event) {
    if ($event->getEntityTypeId() == 'taxonomy_term') {
      $entities = $event->getEntities();
      foreach ($entities[$event->getEntityTypeId()] as $entity_id => $entity) {
        /** @var \Drupal\taxonomy\Entity\Term $entity */
        $parents = $this->entityTypeManager->getStorage("taxonomy_term")->loadParents($entity->id());
        if (!empty($parents)) {
          $entities[$event->getEntityTypeId()][$entity_id]->parent->setValue($parents);
        }
      }
      $event->setEntities($entities);

    }
  }

}
