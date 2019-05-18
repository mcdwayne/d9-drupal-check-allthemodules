<?php

namespace Drupal\depcalc\EventSubscriber\DependencyCollector;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\depcalc\DependencyCalculatorEvents;
use Drupal\depcalc\DependentEntityWrapper;
use Drupal\depcalc\Event\CalculateEntityDependenciesEvent;

/**
 * Subscribes to dependency collection to extract the entity form display.
 */
class EntityFormDisplayDependencyCollector extends BaseDependencyCollector {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityFormDisplayDependencyCollector constructor.
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
   * Calculates the associated entity form display.
   *
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *   The dependency calculation event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onCalculateDependencies(CalculateEntityDependenciesEvent $event) {
    if ($event->getEntity() instanceof ContentEntityInterface) {
      $storage = $this->entityTypeManager->getStorage('entity_form_display');
      $entity = $event->getEntity();
      $ids = $this->entityTypeManager->getStorage('entity_form_display')
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

  /**
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *
   * @todo Determine if commented code is desirable functionality or not.
   *
   * The entity_view_displays often don't exist for simple entity types like
   * taxonomy terms until someone has interacted with the "manage display" tab.
   * This indicates that the fields or the display of fields may have been
   * altered in some relevant way, but since terms don't have any attached
   * fields by default, they also don't get a corresponding entity_view_display
   * object until that changes or there has been some sort of interaction that
   * caused it to come into existence. This may actually be desirable for us
   * to not create and export the entity, but the inverse may actually prove to
   * be true as well, so the code has been left for later evaluation.
   */
//  public function onCalculateDependencies(CalculateEntityDependenciesEvent $event) {
//    $storage = \Drupal::entityTypeManager()->getStorage('entity_form_display');
//    $entity = $event->getEntity();
//    $ids = \Drupal::entityQuery('entity_form_display')
//      ->condition('status', TRUE)
//      ->condition('targetEntityType', $entity->getEntityTypeId())
//      ->condition('bundle', $entity->bundle())
//      ->execute();
//    if ($ids) {
//      $displays = $storage->loadMultiple($ids);
//      $new_display = NULL;
//    }
//    else {
//      $new_display = $storage->create([
//        'targetEntityType' => $entity->getEntityTypeId(),
//        'bundle' => $entity->bundle(),
//        'mode' => 'default',
//        'status' => TRUE,
//      ]);
//      $new_display->save();
//      $displays = [$new_display->id() => $new_display];
//    }
//      foreach ($displays as $display) {
//        $dependencies = $event->getDependencies();
//        $dependencies = $event->getCalculator()->calculateDependencies($display, $dependencies);
//        $event->setDependencies($dependencies);
//      }
//      if ($new_display) {
//        $new_display->delete();
//      $event->stopPropagation();
//    }
//  }

}
