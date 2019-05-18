<?php

namespace Drupal\apitools;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines an interface for model controllers.
 *
 * This interface can be implemented by entity handlers that require
 * dependency injection.
 *
 * @ingroup apitools
 */
interface ModelControllerInterface {

  /**
   * Instantiates a new instance of this model controller.
   *
   * @see \Drupal\Core\Entity\EntityHandlerInterface::createInstance()
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this object should use.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @return static
   *   A new instance of the model controller.
   */
  public static function createInstance(ContainerInterface $container, array $configuration);

  /**
   * Routes dynamic methods to custom defined functions.
   *
   * @param $name
   *   Method name.
   * @param $arguments
   *   Method arguments.
   * @return mixed
   */
  public function __call($name, $arguments);
}

