<?php

namespace Drupal\depcalc\EventSubscriber\DependencyCollector;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\depcalc\DependencyCalculatorEvents;
use Drupal\depcalc\DependentEntityWrapper;
use Drupal\depcalc\Event\CalculateEntityDependenciesEvent;

/**
 * Subscribes to dependency collection to extract the entity view display.
 */
class EntityViewDisplayDependencyCollector extends BaseDependencyCollector {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityViewDisplayDependencyCollector constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[DependencyCalculatorEvents::CALCULATE_DEPENDENCIES][] = ['onCalculateDependencies'];
    return $events;
  }

  /**
   * Calculates the associated entity view display.
   *
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *   The dependency calculation event.
   */
  public function onCalculateDependencies(CalculateEntityDependenciesEvent $event) {
    if ($event->getEntity() instanceof ContentEntityInterface) {
      $storage = $this->entityTypeManager->getStorage('entity_view_display');
      $entity = $event->getEntity();
      $ids = $this->entityTypeManager->getStorage('entity_view_display')
        ->getQuery('AND')
        ->condition('status', TRUE)
        ->condition('targetEntityType', $entity->getEntityTypeId())
        ->condition('bundle', $entity->bundle())
        ->execute();
      if ($ids) {
        $displays = $storage->loadMultiple($ids);
        foreach ($displays as $display) {
          $display_wrapper = new DependentEntityWrapper($display);
          $local_dependencies = [];
          $this->mergeDependencies($display_wrapper, $event->getStack(), $this->getCalculator()->calculateDependencies($display_wrapper, $event->getStack(), $local_dependencies));
          $event->addDependency($display_wrapper);
        }
      }
    }
  }

}
