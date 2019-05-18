<?php

namespace Drupal\integro;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an integration definition.
 */
interface DefinitionInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Gets the integration ID.
   *
   * @return string
   *   The integration ID.
   */
  public function id();

  /**
   * Gets the integration ID.
   *
   * @return string
   *   The integration ID.
   */
  public function getId();

  /**
   * Sets the integration ID.
   *
   * @param string $id
   *   The integration ID.
   *
   * @return $this
   */
  public function setId($id);

  /**
   * Gets the integration version.
   *
   * @return string
   *   The integration version.
   */
  public function getVersion();

  /**
   * Sets the integration version.
   *
   * @param string $version
   *   The integration version.
   *
   * @return $this
   */
  public function setVersion($version);

  /**
   * Gets the integration parent.
   *
   * @return string
   *   The integration parent.
   */
  public function getParent();

  /**
   * Sets the integration parent.
   *
   * @param string $parent
   *   The integration parent.
   *
   * @return $this
   */
  public function setParent($parent);

  /**
   * Gets the integration definition plugin ID.
   *
   * @return string
   *   The integration definition plugin ID.
   */
  public function getDefinition();

  /**
   * Sets the integration definition plugin ID.
   *
   * @param string $definition
   *   The integration definition plugin ID.
   *
   * @return $this
   */
  public function setDefinition($definition);

  /**
   * Gets the integration client plugin.
   *
   * @param array $configuration
   * @return string The integration client plugin.
   *   The integration client plugin.
   */
  public function getClientPlugin($configuration = []);

  /**
   * Gets the integration client plugin ID.
   *
   * @return string
   *   The integration client plugin ID.
   */
  public function getClientPluginId();

  /**
   * Sets the integration client plugin ID.
   *
   * @param string $client_plugin_id
   *   The integration client plugin ID.
   *
   * @return $this
   */
  public function setClientPluginId($client_plugin_id);

  /**
   * Gets the label.
   *
   * @return mixed
   *   The label.
   */
  public function getLabel();

  /**
   * Sets the integration label.
   *
   * @param string $label
   *   The label.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Gets the description.
   *
   * @return mixed
   *   The description.
   */
  public function getDescription();

  /**
   * Sets the integration description.
   *
   * @param string $description
   *   The description.
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Gets the category.
   *
   * @return mixed
   *   The category.
   */
  public function getCategory();

  /**
   * Sets the integration category.
   *
   * @param string $category
   *   The category.
   *
   * @return $this
   */
  public function setCategory($category);

  /**
   * Gets the integration plugin ID.
   *
   * @return string
   *   The integration.
   */
  public function getIntegrationPluginId();

  /**
   * Sets the integration.
   *
   * @param string $integration_plugin_id
   *   The integration.
   *
   * @return $this
   */
  public function setIntegrationPluginId($integration_plugin_id);

  /**
   * Gets the provider.
   *
   * @return string
   *   The integration provider.
   */
  public function getProvider();

  /**
   * Sets the integration provider.
   *
   * @param string $provider
   *   The provider.
   *
   * @return $this
   */
  public function setProvider($provider);

  /**
   * Gets the operations.
   *
   * @return \Drupal\integro\OperationInterface[]
   *   The integration operations.
   */
  public function getOperations();

  /**
   * Sets the integration operations.
   *
   * @param \Drupal\integro\OperationInterface[] $operations
   *   The operations.
   *
   * @return $this
   */
  public function setOperations($operations);

}
