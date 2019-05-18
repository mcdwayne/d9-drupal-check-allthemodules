<?php

/**
 * @file Contains \Drupal\powertagging_similar\Entity\PowerTaggingSimilarConfigInterface.
 */

namespace Drupal\powertagging_similar\Entity;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

interface PowerTaggingSimilarConfigInterface extends ConfigEntityInterface {

  /**
   * Getter-function for the title-variable.
   *
   * @return string
   *   Title of the configuration set
   */
  public function getTitle();

  /**
   * Set the title of the PowerTaggingSimilar configuration.
   *
   * @param string $title
   *   The title of the PowerTaggingSimilar configuration.
   */
  public function setTitle($title);

  /**
   * Getter-function for the ID of the PowerTagging configuration.
   *
   * @return string
   *   PowerTagging server ID of the configuration set
   */
  public function getPowerTaggingId();

  /**
   * Set the ID of the PowerTagging configuration.
   *
   * @param string $powertagging_id
   *   The ID of the PowerTagging configuration.
   */
  public function setPowerTaggingId($powertagging_id);

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
   * Helper function to check whether an powertagging_similar entity with a specific
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