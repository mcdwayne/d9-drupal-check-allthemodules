<?php

namespace Drupal\depcalc\Event;

use Drupal\Core\Entity\EntityInterface;
use Drupal\layout_builder\SectionComponent;
use Symfony\Component\EventDispatcher\Event;

class SectionComponentDependenciesEvent extends Event {

  /**
   * The component for this event.
   *
   * @var \Drupal\layout_builder\SectionComponent
   */
  protected $component;

  /**
   * The entity dependencies for this event.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $entityDependencies;

  /**
   * The module dependencies for this event.
   *
   * @var string[]
   */
  protected $moduleDependencies;

  public function __construct(SectionComponent $component) {
    $this->component = $component;
  }

  /**
   * Get the event component.
   *
   * @return \Drupal\layout_builder\SectionComponent
   */
  public function getComponent() {
    return $this->component;
  }

  /**
   * Get the entity dependencies for this event.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  public function getEntityDependencies() {
    return $this->entityDependencies ? : [];
  }

  /**
   * Get the module dependencies for this event.
   *
   * @return string[]
   */
  public function getModuleDependencies() {
    return $this->moduleDependencies ? : [];
  }

  /**
   * Adds an entity as dependency.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function addEntityDependency(EntityInterface $entity) {
    $this->entityDependencies[] = $entity;
  }

  /**
   * Adds a module as dependency.
   *
   * @param string $module
   */
  public function addModuleDependency(string $module) {
    $this->moduleDependencies[] = $module;
  }
}
