<?php

namespace Drupal\jsonrpc;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Interface for all parameter factories.
 */
interface ParameterFactoryInterface {

  /**
   * An array representing the JSON Schema for acceptable input to the factory.
   *
   * @param \Drupal\jsonrpc\ParameterDefinitionInterface $parameter_definition
   *   A parameter definition for the method parameter being constructed.
   *
   * @return array
   *   The JSON Schema.
   */
  public static function schema(ParameterDefinitionInterface $parameter_definition);

  /**
   * Instantiates a new instance of this class.
   *
   * This is a factory method that returns a new instance of this class. The
   * factory should pass any needed dependencies into the constructor of this
   * class, but not the container itself. Every call to this method must return
   * a new instance of this class; that is, it may not implement a singleton.
   *
   * @param \Drupal\jsonrpc\ParameterDefinitionInterface $definition
   *   The parameter definition for parameters of this type.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   */
  public static function create(ParameterDefinitionInterface $definition, ContainerInterface $container);

}
