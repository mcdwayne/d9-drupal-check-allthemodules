<?php

/**
 * @file Contains \Drupal\semantic_connector\Entity\SemanticConnectorConnectionInterface.
 */

namespace Drupal\semantic_connector\Entity;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface SemanticConnectorConnectionInterface extends ConfigEntityInterface
{
  /**
   * Check if a connection is available.
   *
   * @return bool
   *   TRUE if the connection is available, FALSE if not.
   */
  public function available();

  /**
   * Get an already configured API of a connection.
   *
   * @param string $api_type
   *   Additional information on what API to get, if a connection supports more
   *   than one APIs.
   *
   * @return object
   *   An API-object, depending on the $api_type.
   */
  public function getApi($api_type = '');

  /**
   * Get the default config of the Semantic Connector Connection.
   *
   * @return array
   *   The default config of the Semantic Connector Connection.
   */
  public function getDefaultConfig();

  /**
   * Get the ID of the Semantic Connector Connection.
   *
   * @return int
   *   The ID of the Semantic Connector Connection.
   */
  public function getId();

  /**
   * Get the type of the Semantic Connector Connection.
   *
   * @return string
   *   The type of the Semantic Connector Connection.
   */
  public function getType();

  /**
   * Set the type of the Semantic Connector Connection.
   *
   * @param string $type
   *   The type of the Semantic Connector Connection.
   */
  public function setType($type);

  /**
   * Get the URL of the Semantic Connector Connection.
   *
   * @return string
   *   The URL of the Semantic Connector Connection.
   */
  public function getUrl();

  /**
   * Set the URL of the Semantic Connector Connection.
   *
   * @param string $url
   *   The URL of the Semantic Connector Connection.
   */
  public function setUrl($url);

  /**
   * Get the credentials of the Semantic Connector Connection.
   *
   * @return array
   *   An array with the credentials of the Semantic Connector Connection,
   *   including properties "username" and "password" .
   */
  public function getCredentials();

  /**
   * Set the credentials of the Semantic Connector Connection.
   *
   * @param array $credentials
   *   An array with the credentials of the Semantic Connector Connection,
   *   including properties "username" and "password" .
   */
  public function setCredentials(array $credentials);

  /**
   * Get the title of the Semantic Connector Connection.
   *
   * @return string
   *   The title of the Semantic Connector Connection.
   */
  public function getTitle();

  /**
   * Set the title of the Semantic Connector Connection.
   *
   * @param string $title
   *   The title of the Semantic Connector Connection.
   */
  public function setTitle($title);

  /**
   * Get the config of the Semantic Connector Connection.
   *
   * @return array
   *   The config of the Semantic Connector Connection.
   */
  public function getConfig();

  /**
   * Set the config of the Semantic Connector Connection.
   *
   * @param array $config
   *   The config of the Semantic Connector Connection as an array.
   */
  public function setConfig(array $config);
}