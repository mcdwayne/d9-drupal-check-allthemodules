<?php

namespace Drupal\jsonrpc;

/**
 * Interface to implement a parameter definition.
 */
interface ParameterDefinitionInterface {

  /**
   * The name of the parameter if the params are by-name, an offset otherwise.
   *
   * @return string|int
   *   The ID.
   */
  public function getId();

  /**
   * The description of the parameter for the method.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The description.
   */
  public function getDescription();

  /**
   * Whether the parameter is required.
   *
   * @return bool
   *   True if this is a required parameter.
   */
  public function isRequired();

  /**
   * Gets the parameter schema.
   *
   * Can be derived from the type when the schema property is not defined.
   *
   * @return array
   *   The schema.
   */
  public function getSchema();

  /**
   * Get the parameter factory class.
   *
   * @return string
   *   The parameter factory.
   */
  public function getFactory();

}
