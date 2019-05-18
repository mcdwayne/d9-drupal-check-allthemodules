<?php

/**
 * @file Contains \Drupal\pp_taxonomy_manager\Entity\PPTaxonomyManagerConfigInterface.
 */

namespace Drupal\pp_taxonomy_manager\Entity;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\semantic_connector\Entity\SemanticConnectorConnection;

interface PPTaxonomyManagerConfigInterface extends ConfigEntityInterface {

  /**
   * Getter-function for the project_id-variable.
   *
   * @return string
   *   ID of the used PoolParty project.
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
   * Getter-function for the title-variable.
   *
   * @return string
   *   Title of the configuration set
   */
  public function getTitle();

  /**
   * Set the title of the PPTaxonomyManager configuration.
   *
   * @param string $title
   *   The title of the PPTaxonomyManager configuration.
   */
  public function setTitle($title);

  /**
   * Getter-function for the PoolParty Taxonomy Manager server ID.
   *
   * @return int
   *   PoolParty Taxonomy Manager server ID of the configuration set
   */
  public function getConnectionId();

  /**
   * Set the connection ID of the PoolParty Taxonomy Manager server.
   *
   * @param string $connection_id
   *   The ID of the SemanticConnectorPPServerConnection.
   */
  public function setConnectionId($connection_id);

  /**
   * Getter-function for the PoolParty Taxonomy Manager server connection object.
   *
   * @return SemanticConnectorConnection
   *   PoolParty Taxonomy Manager server connection object of the configuration set
   */
  public function getConnection();

  /**
   * Getter-function for the config-variable.
   *
   * @return array
   *   Config of the configuration set
   */
  public function getConfig();

  /**
   * Setter-function for the config-variable.
   *
   * @param array $config
   *   Config of the configuration set
   */
  public function setConfig($config);

  /**
   * Get the default config of a configuration set.
   *
   * @return array
   *   Config of the configuration set
   */
  public static function getDefaultConfig();

  /**
   * Helper function to check whether an pp_taxonomy_manager entity with a specific
   * ID exists.
   *
   * @param string $id
   *   The ID to check if there is an entity for.
   *
   * @return bool
   *   TRUE if an entity with this ID already exists, FALSE if not.
   */
  public static function exist($id);

  /**
   * Get the last synchronization log of the taxonomy manager config.
   *
   * @param int $vid
   *   Optional; The vocabulary ID to filter by. Use 0 to not filter by a vid.
   *
   * @return array
   *   An associative array containing start time, end time, user ID and user
   *   name of the last log.
   */
  public function getLastLog($vid = 0);
}