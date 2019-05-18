<?php

namespace Drupal\lightspeed_ecom;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Lightspeed eCom Shop entities.
 */
interface ShopInterface extends ConfigEntityInterface {

  const DEFAULT_ID = 'default';

  /**
   * Gets the Lightspeed eCom Shop Cluster ID.
   *
   * @return string
   *   The cluster ID.
   */
  public function clusterId();

  /**
   * Sets the Lightspeed eCom Shop Cluster ID.
   *
   * @param string $cluster_id
   *   The cluster ID.
   *
   * @return $this
   */
  public function setClusterId($cluster_id);

  /**
   * Gets the Lightspeed eCom Shop API Key.
   *
   * @return string
   *   The cluster ID.
   */
  public function apiKey();

  /**
   * Sets the Lightspeed eCom Shop API Key.
   *
   * @param string $api_key
   *   The API Key.
   *
   * @return $this
   */
  public function setApiKey($api_key);

  /**
   * Gets the Lightspeed eCom Shop API Secret.
   *
   * @return string
   *   The cluster ID.
   */
  public function apiSecret();

  /**
   * Sets the Lightspeed eCom Shop API Secret.
   *
   * @param string $api_secret
   *   The API Secret.
   *
   * @return $this
   */
  public function setApiSecret($api_secret);

}
