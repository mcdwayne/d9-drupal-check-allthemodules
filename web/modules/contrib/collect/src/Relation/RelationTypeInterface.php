<?php
/**
 * @file
 * Contains \Drupal\collect\Relation\RelationTypeInterface.
 */

namespace Drupal\collect\Relation;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for relation type entities.
 */
interface RelationTypeInterface extends ConfigEntityInterface {

  /**
   * Returns the URI pattern.
   *
   * @return string
   *   The URI pattern.
   */
  public function getUriPattern();

  /**
   * Sets the URI pattern.
   *
   * @param string $uri_pattern
   *   The new URI pattern.
   *
   * @return $this
   */
  public function setUriPattern($uri_pattern);

  /**
   * Returns the plugin ID.
   *
   * @return string
   *   The plugin ID.
   */
  public function getPluginId();

  /**
   * Sets the plugin ID.
   *
   * @param string $plugin_id
   *   The new plugin ID.
   *
   * @return $this
   */
  public function setPluginId($plugin_id);

}
