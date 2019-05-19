<?php

namespace Drupal\widencollective;

/**
 * Interface WidencollectiveSearchServiceInterface.
 *
 * @package Drupal\widencollective
 */
interface WidencollectiveSearchServiceInterface {

  /**
   * Returns widen setting config where it stores the authentication data.
   */
  public static function getConfig();

  /**
   * Executes a request to widen api to fetch search UI url.
   *
   * @param string $access_token
   *   Widen user token.
   *
   * @return array
   *   Returns an array.
   */
  public static function getSearchConnectorUiUrl($access_token);

}
