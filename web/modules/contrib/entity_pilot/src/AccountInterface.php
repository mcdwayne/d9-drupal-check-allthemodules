<?php

namespace Drupal\entity_pilot;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines an interface for working with an Entity Pilot account.
 */
interface AccountInterface extends ConfigEntityInterface {

  /**
   * Returns the Carrier ID set for this account.
   *
   * @return string
   *   The carrier id for this account.
   */
  public function getCarrierId();

  /**
   * Returns the black box (private) key for this account.
   *
   * This value should be treated the same as a private key or password.
   *
   * @return string
   *   The black box key.
   */
  public function getBlackBoxKey();

  /**
   * Returns the description of this account.
   *
   * @return string
   *   The account description
   */
  public function getDescription();

  /**
   * Returns the account secret.
   *
   * @return string
   *   The account secret.
   */
  public function getSecret();

  /**
   * Sets the account secret.
   *
   * @param string $secret
   *   The account secret.
   *
   * @return $this
   */
  public function setSecret($secret);

  /**
   * Gets value of LegacySecret.
   *
   * @return string
   *   Value of LegacySecret.
   */
  public function getLegacySecret();

  /**
   * Sets value of LegacySecret.
   *
   * @param string $legacySecret
   *   Value for LegacySecret.
   *
   * @return $this
   */
  public function setLegacySecret($legacySecret);

}
