<?php

namespace Drupal\depcalc\Event;

use Drupal\depcalc\DependencyStack;
use Drupal\depcalc\DependentEntityWrapperInterface;
use Symfony\Component\EventDispatcher\Event;

class CalculateEntityDependenciesEvent extends Event {

  /**
   * The wrapper of the entity for which we are calculating dependencies.
   *
   * @var \Drupal\depcalc\DependentEntityWrapperInterface
   */
  protected $wrapper;

  /**
   * The dependency stack.
   *
   * @var \Drupal\depcalc\DependencyStack
   */
  protected $stack;

  /**
   * CalculateEntityDependenciesEvent constructor.
   *
   * @param \Drupal\depcalc\DependentEntityWrapperInterface $wrapper
   *   The entity for which we are calculating dependencies.
   * @param \Drupal\depcalc\DependencyStack $stack
   *   The dependency stack.
   */
  public function __construct(DependentEntityWrapperInterface $wrapper, DependencyStack $stack) {
    $this->wrapper = $wrapper;
    $this->stack = $stack;
  }

  /**
   * Get the dependency wrapper of the entity.
   *
   * @return \Drupal\depcalc\DependentEntityWrapperInterface
   */
  public function getWrapper() {
    return $this->wrapper;
  }

  /**
   * Get the entity for which dependencies are being calculated.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity() {
    return $this->wrapper->getEntity();
  }

  /**
   * Get the dependency stack.
   *
   * @return \Drupal\depcalc\DependencyStack
   */
  public function getStack() {
    return $this->stack;
  }

  /**
   * Add a dependency to this wrapper.
   *
   * @param \Drupal\depcalc\DependentEntityWrapperInterface $dependency
   */
  public function addDependency(DependentEntityWrapperInterface $dependency) {
    $dependencies = $this->getWrapper()->getDependencies();
    if (!array_key_exists($dependency->getUuid(), $dependencies)) {
      $this->getWrapper()->addDependency($dependency, $this->getStack());
    }
  }

  /**
   * Add a group of dependencies to this wrapper.
   *
   * @param \Drupal\depcalc\DependentEntityWrapperInterface ...$dependencies
   *   The dependencies to add to this wrapper.
   */
  public function setDependencies(DependentEntityWrapperInterface ...$dependencies) {
    foreach ($dependencies as $key => $dependency) {
      $this->addDependency($dependency);
    }
  }

  /**
   * A list of all uuids this entity is dependent on.
   *
   * @return \Drupal\depcalc\DependentEntityWrapperInterface[]
   */
  public function getDependencies() {
    return $this->stack->getDependenciesByUuid(array_keys($this->getWrapper()->getDependencies()));
  }

  /**
   * A list of modules this entity depends upon.
   *
   * @return string[]
   */
  public function getModuleDependencies() {
    return $this->getWrapper()->getModuleDependencies();
  }

  /**
   * A list of module dependencies to add to this wrapper.
   *
   * @param string[] $modules
   *   The list of modules.
   */
  public function setModuleDependencies(array $modules) {
    $this->getWrapper()->addModuleDependencies($modules);
  }

}
