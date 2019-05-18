<?php

namespace Drupal\gclient;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides an interface for an entity manager service.
 */
interface ProjectManagerInterface {

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
   * Returns the list of all entities as options.
   *
   * @return array
   */
  public function getOptions();

}
