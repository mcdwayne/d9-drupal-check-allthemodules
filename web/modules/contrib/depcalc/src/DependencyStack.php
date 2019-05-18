<?php

namespace Drupal\depcalc;

class DependencyStack {

  protected $dependencies = [];

  protected $additional_processing = [];

  /**
   * DependencyStack constructor.
   *
   * @param \Drupal\depcalc\DependentEntityWrapperInterface ...$dependencies
   *   Any previously built dependency to prevent recalculation.
   */
  public function __construct(DependentEntityWrapperInterface ...$dependencies) {
    foreach ($dependencies as $dependency) {
      $this->dependencies[$dependency->getRemoteUuid()] = $dependency;
    }
  }

  /**
   * Add a dependency to the stack.
   *
   * @param \Drupal\depcalc\DependentEntityWrapperInterface $dependency
   *   The dependency to add to the stack.
   */
  public function addDependency(DependentEntityWrapperInterface $dependency) {
    $this->dependencies[$dependency->getRemoteUuid()] = $dependency;
    if ($dependency->needsAdditionalProcessing()) {
      $this->additional_processing[$dependency->getRemoteUuid()] = '';
    }
    else {
      unset($this->additional_processing[$dependency->getRemoteUuid()]);
    }
  }

  /**
   * Get a specific dependency from the stack.
   *
   * @param string $uuid
   *   The uuid of the dependency to retrieve.
   *
   * @return \Drupal\depcalc\DependentEntityWrapperInterface
   */
  public function getDependency($uuid) {
    if (!empty($this->dependencies[$uuid])) {
      return $this->dependencies[$uuid];
    }
  }

  /**
   * Checks if a particular dependency exists in the stack.
   *
   * @param string $uuid
   *   The uuid of the dependency to check.
   *
   * @return bool
   */
  public function hasDependency($uuid) {
    return !empty($this->dependencies[$uuid]);
  }

  public function hasDependencies(array $targets) {
    $count = count($targets);
    $targets = array_combine($targets, $targets);
    $intersection = array_intersect_key($targets, $this->dependencies);
    $equal = count($intersection) === $count;
    if ($equal) {
      $intersection = array_intersect_key($targets, $this->additional_processing);
      if ($intersection) {
        return FALSE;
      }
    }
    return $equal;
  }

  /**
   * Get a specific set of dependencies.
   *
   * @param string[] $dependencies
   *   The list of dependencies, by uuid, to retrieve.
   *
   * @return \Drupal\depcalc\DependentEntityWrapperInterface[]
   */
  public function getDependenciesByUuid(array $dependencies) {
    $results = [];
    foreach ($dependencies as $uuid) {
       $results[$uuid] = $this->getDependency($uuid);
    }
    return $results;
  }

  /**
   * @return \Drupal\depcalc\DependentEntityWrapperInterface[]
   */
  public function getDependencies() {
    return $this->dependencies;
  }

}
