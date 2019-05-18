<?php

namespace Drupal\depcalc\EventSubscriber\DependencyCollector;

use Drupal\Core\Database\Connection;
use Drupal\depcalc\DependencyCalculatorEvents;
use Drupal\depcalc\DependentEntityWrapper;
use Drupal\depcalc\Event\CalculateEntityDependenciesEvent;

class TermParentCollector extends BaseDependencyCollector {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * TermParentCollector constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function getSubscribedEvents() {
    $events[DependencyCalculatorEvents::CALCULATE_DEPENDENCIES][] = ['onCalculateDependencies'];
    return $events;
  }

  /**
   * Add parent terms to dependency calculation.
   *
   * @param \Drupal\depcalc\Event\CalculateEntityDependenciesEvent $event
   *   The CalculateEntityDependenciesEvent event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onCalculateDependencies(CalculateEntityDependenciesEvent $event) {
    if ($event->getWrapper()->getEntityTypeId() == 'taxonomy_term') {
      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = $event->getEntity();
      /** @var \Drupal\taxonomy\TermStorage $storage */
      $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $parents = $storage->loadParents($term->id());
      foreach($parents as $parent) {
        if (!$event->getStack()->hasDependency($parent->uuid())) {
          $parent_wrapper = new DependentEntityWrapper($parent);
          $local_dependencies = [];
          $this->mergeDependencies($parent_wrapper, $event->getStack(), $this->getCalculator()
            ->calculateDependencies($parent_wrapper, $event->getStack(), $local_dependencies));
          $event->addDependency($parent_wrapper);
          // Child term's dependencies already calculated. Adding parent is sufficient.
        }
      }
    }
  }

}
