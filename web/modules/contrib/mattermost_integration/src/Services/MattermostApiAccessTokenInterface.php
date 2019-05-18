<?php

namespace Drupal\mattermost_integration\Services;

/**
 * Interface MattermostApiAccessTokenInterface.
 *
 * @package Drupal\mattermost_integration\Services
 */
interface MattermostApiAccessTokenInterface {

  /**
   * Get the access token of Mattermost.
   *
   * @param bool $reset
   *   If access token should be reset.
   *
   * @return string
   *   Return access token of Mattermost.
   */
  public function getAccessToken($reset = FALSE);

}
