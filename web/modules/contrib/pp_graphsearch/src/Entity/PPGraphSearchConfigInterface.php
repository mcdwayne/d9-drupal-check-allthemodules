<?php

/**
 * @file Contains \Drupal\pp_graphsearch\Entity\PPGraphSearchConfigInterface.
 */

namespace Drupal\pp_graphsearch\Entity;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\semantic_connector\Entity\SemanticConnectorConnection;

interface PPGraphSearchConfigInterface extends ConfigEntityInterface {

  /**
   * Getter-function for the search_space_id-variable.
   *
   * @return string
   *   ID of the used GraphSearch search space.
   */
  public function getSearchSpaceId();

  /**
   * Setter-function for the search_space_id-variable.
   *
   * @param string $search_space_id
   *   ID of the used GraphSearch search space.
   */
  public function setSearchSpaceId($search_space_id);

  /**
   * Getter-function for the title-variable.
   *
   * @return string
   *   Title of the configuration set
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
   * Getter-function for the PoolParty GraphSearch server ID.
   *
   * @return int
   *   PoolParty GraphSearch server ID of the configuration set
   */
  public function getConnectionId();

  /**
   * Set the connection ID of the PoolParty GraphSearch server.
   *
   * @param string $connection_id
   *   The ID of the SemanticConnectorPPServerConnection.
   */
  public function setConnectionId($connection_id);

  /**
   * Getter-function for the PoolParty GraphSearch server connection object.
   *
   * @return SemanticConnectorConnection
   *   PoolParty GraphSearch server connection object of the configuration set
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
   * Helper function to check whether an pp_graphsearch entity with a specific
   * ID exists.
   *
   * @param string $id
   *   The ID to check if there is an entity for.
   *
   * @return bool
   *   TRUE if an entity with this ID already exists, FALSE if not.
   */
  public static function exist($id);
}