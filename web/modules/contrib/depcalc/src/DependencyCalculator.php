<?php

namespace Drupal\depcalc;

use Drupal\depcalc\Event\CalculateEntityDependenciesEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Calculates all the dependencies of a given entity.
 *
 * This class calculates all the dependencies of any entity. Pass an entity of
 * any sort to the calculateDependencies() method, and this class will recurse
 * through all the existing inter-dependencies that it knows about. New
 * dependency collectors can be add via the
 */
class DependencyCalculator {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The DependencyCalculator constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $dispatcher) {
    $this->dispatcher = $dispatcher;
  }

  /**
   * @param \Drupal\depcalc\DependentEntityWrapperInterface $wrapper
   *   The dependency wrapper for the entity to calculate dependencies.
   * @param \Drupal\depcalc\DependencyStack $stack
   *   An array of pre-calculated dependencies to prevent recalculation.
   * @param array $dependencies
   *   (optional) An array of dependencies by reference. Internally used.
   *
   * @return array
   */
  public function calculateDependencies(DependentEntityWrapperInterface $wrapper, DependencyStack $stack, array &$dependencies = []) {
    if (empty($dependencies['module'])) {
      $dependencies['module'] = [];
    }
    // Prevent handling the same entity more than once..
    if (!empty($dependencies[$wrapper->getUuid()])) {
      return $dependencies;
    }
    // Prevent handling the same entity more than once..
    if ($stack->hasDependency($wrapper->getUuid())) {
      $dependencies[$wrapper->getUuid()] = $stack->getDependency($wrapper->getUuid());
      return $dependencies;
    }

    $stack->addDependency($wrapper);
    $event = new CalculateEntityDependenciesEvent($wrapper, $stack);
    $this->dispatcher->dispatch(DependencyCalculatorEvents::CALCULATE_DEPENDENCIES, $event);

    $modules = $event->getModuleDependencies();
    if ($modules) {
      $wrapper->addModuleDependencies($modules);
    }
    $dependencies = $stack->getDependenciesByUuid(array_keys($event->getDependencies()));
    $wrapper->addDependencies($stack, ...array_values($dependencies));
    $dependencies[$wrapper->getUuid()] = $wrapper;
    $dependencies['module'] = $event->getModuleDependencies();
    return $dependencies;
  }

}
