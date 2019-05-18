<?php

/**
 * @file Contains \Drupal\powertagging\Entity\PowerTaggingConfigInterface.
 */

namespace Drupal\powertagging\Entity;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for defining PowerTagging entities.
 */
interface PowerTaggingConfigInterface extends EntityInterface, ConfigEntityInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * The Getter method for the title variable.
   *
   * @return string
   *   The title of the PowerTagging configuration.
   */
  public function getTitle();

  /**
   * Set the title of the PPGraphSearch configuration.
   *
   * @param string $title
   *   The title of the PPGraphSearch configuration.
   */
  public function setTitle($title);

  /**
   * The Getter method for the connection variable.
   *
   * @return \Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection
   *   The connection to the PoolParty server.
   */
  public function getConnection();

  /**
   * The Getter method for the connection_id variable.
   *
   * @return string
   *   The ID of the PoolParty server connection.
   */
  public function getConnectionId();

  /**
   * Set the connection ID of the PoolParty server.
   *
   * @param string $connection_id
   *   The ID of the SemanticConnectorPPServerConnection.
   */
  public function setConnectionId($connection_id);

  /**
   * The Getter method for the project_id variable.
   *
   * @return string
   *   The ID of the PoolParty project.
   */
  public function getProjectId();

  /**
   * Setter-function for the project_id-variable.
   *
   * @param string $project_id
   *   ID of the used PoolParty project.
   */
  public function setProjectId($project_id);

  /**
   * The Getter method for the config variable.
   *
   * @return array
   *   The additional configuration of the PowerTagging.
   */
  public function getConfig();

  /**
   * Setter-function for the config-variable.
   *
   * @param array $config
   *   Config of the configuration set
   */
  public function setConfig($config);
}
