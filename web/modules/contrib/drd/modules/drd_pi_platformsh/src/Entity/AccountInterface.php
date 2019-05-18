<?php

namespace Drupal\drd_pi_platformsh\Entity;

/**
 * Provides an interface for defining Account entities.
 */
interface AccountInterface {

  /**
   * API token of this account.
   *
   * @return string
   *   API token.
   */
  public function getApiToken();

  /**
   * Set the API token of this account.
   *
   * @param string $apiToken
   *   API token.
   *
   * @return $this
   */
  public function setApiToken($apiToken);

}
