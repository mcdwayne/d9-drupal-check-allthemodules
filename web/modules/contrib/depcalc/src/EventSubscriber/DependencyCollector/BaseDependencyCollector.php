<?php

namespace Drupal\depcalc\EventSubscriber\DependencyCollector;

use Drupal\depcalc\DependencyStack;
use Drupal\depcalc\DependentEntityWrapperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An abstract class with helpful methods for all dependency collectors.
 */
abstract class BaseDependencyCollector implements EventSubscriberInterface {

  /**
   * Properly adds dependencies and their modules to a wrapper object.
   *
   * @param \Drupal\depcalc\DependentEntityWrapperInterface $wrapper
   *   The object to add dependencies to.
   * @param \Drupal\depcalc\DependencyStack $stack
   *   The stack with all loaded dependencies.
   * @param array $dependencies
   *   The list of dependencies to add to the wrapper.
   */
  protected function mergeDependencies(DependentEntityWrapperInterface $wrapper, DependencyStack $stack, array $dependencies) {
    $modules = !empty($dependencies['module']) ? $dependencies['module'] : [];
    unset($dependencies['module']);
    $wrapper->addDependencies($stack, ...array_values($dependencies));
    if ($modules) {
      $wrapper->addModuleDependencies($modules);
    }
  }

  /**
   * Gets the dependency calculator.
   *
   * @return \Drupal\depcalc\DependencyCalculator
   */
  protected function getCalculator() {
    return \Drupal::service('entity.dependency.calculator');
  }

}
