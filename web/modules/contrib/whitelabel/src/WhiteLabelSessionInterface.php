<?php

namespace Drupal\whitelabel;

/**
 * Stores the active white label in a user session.
 *
 * Allows the system to set and retrieve the active white label within a
 * session.
 *
 * @see \Drupal\whitelabel\WhiteLabelProviderInterface
 */
interface WhiteLabelSessionInterface {

  /**
   * Gets the current white label from the session.
   *
   * @param string $key
   *   The session key to fetch from. Default should be good for most cases.
   *
   * @return int|null
   *   The white label id, or NULL if there is no white label set.
   */
  public function getWhiteLabelId($key = 'whitelabel');

  /**
   * Sets a given white label in the session.
   *
   * @param int|null $whitelabel_id
   *   The id of the white label object to activate.
   * @param string $key
   *   The session key to save to. Default should be good for most cases.
   */
  public function setWhiteLabelId($whitelabel_id, $key = 'whitelabel');

}
