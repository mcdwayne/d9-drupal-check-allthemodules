<?php

namespace Drupal\drd_pi_acquia\Entity;

/**
 * Provides an interface for defining Account entities.
 */
interface AccountInterface {

  /**
   * Email of this account.
   *
   * @return string
   *   Email address.
   */
  public function getEmail();

  /**
   * Private key of this account.
   *
   * @return string
   *   Private key.
   */
  public function getPrivateKey();

  /**
   * Set the private key of this account.
   *
   * @param string $privateKey
   *   Private key.
   *
   * @return $this
   */
  public function setPrivateKey($privateKey);

}
