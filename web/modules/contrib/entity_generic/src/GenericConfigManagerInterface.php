<?php

namespace Drupal\entity_generic;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an interface for an entity manager service.
 */
interface GenericConfigManagerInterface {

  /**
   * Instantiates a new instance of this entity handler.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   *
   * @return static
   *   A new instance of the entity handler.
   */
  public static function create(ContainerInterface $container);

  /**
   * Returns the list of all entities.
   *
   * @return array
   */
  public function getAll();

  /**
   * Returns the list of key-value pairs for all entities what will be used in select/radio widgets.
   *
   * @return array
   */
  public function getOptions();

}
