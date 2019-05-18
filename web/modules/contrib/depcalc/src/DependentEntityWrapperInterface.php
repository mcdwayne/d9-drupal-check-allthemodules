<?php

namespace Drupal\depcalc;

interface DependentEntityWrapperInterface {

  /**
   * Get the entity for which we are collecting dependencies.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity();

  /**
   * The id of the entity.
   *
   * @return int|null|string
   */
  public function getId();

  /**
   * The uuid of the entity.
   *
   * @return null|string
   */
  public function getUuid();

  public function setRemoteUuid($uuid);

  public function getRemoteUuid();

  /**
   * The entity type id.
   *
   * @return string
   */
  public function getEntityTypeId();

  /**
   * Get the uuid/hash values of dependencies of this entity.
   *
   * @return string[]
   */
  public function getDependencies();

  /**
   * Document a new dependency for this entity.
   *
   * @param \Drupal\depcalc\DependentEntityWrapperInterface $dependency
   *   The dependency to add.
   * @param \Drupal\depcalc\DependencyStack $stack
   *   The dependency stack.
   */
  public function addDependency(DependentEntityWrapperInterface $dependency, DependencyStack $stack);

  /**
   * Add dependencies of this entity.
   *
   * @param \Drupal\depcalc\DependencyStack $stack
   *   The dependency stack.
   * @param \Drupal\depcalc\DependentEntityWrapperInterface ...$dependencies
   *   Entities wrappers to add as a dependency.
   */
  public function addDependencies(DependencyStack $stack, DependentEntityWrapperInterface ...$dependencies);

  /**
   * Add new module dependencies.
   *
   * @param array $modules
   *   The list of modules to add as dependencies.
   */
  public function addModuleDependencies(array $modules);

  /**
   * The list of module dependencies.
   *
   * @return string[]
   */
  public function getModuleDependencies();

  /**
   * The hash value of the entity.
   *
   * @return mixed
   */
  public function getHash();

  public function needsAdditionalProcessing();

}
