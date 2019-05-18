<?php

namespace Drupal\integro;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\integro\Entity\ConnectorInterface;

/**
 * Defines an integration.
 */
interface IntegrationInterface extends PluginInspectionInterface {

  /**
   * Gets the definition.
   *
   * @return \Drupal\integro\DefinitionInterface
   *   The definition.
   */
  public function getDefinition();

  /**
   * Sets the definition.
   *
   * @param \Drupal\integro\DefinitionInterface $definition
   *   The integration definition.
   *
   * @return $this
   */
  public function setDefinition(DefinitionInterface $definition);

  /**
   * Gets the operation.
   *
   * @param \Drupal\integro\Entity\ConnectorInterface $connector
   * @param string $operation
   *   The operation ID.
   * @param array $args
   *   The arguments for the operation.
   *
   * @return \Drupal\integro\OperationInterface
   */
  public function operation(ConnectorInterface $connector, $operation, array $args);

}
