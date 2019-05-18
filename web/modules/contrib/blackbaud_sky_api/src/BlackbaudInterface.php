<?php

namespace Drupal\blackbaud_sky_api;

/**
 * Interface BlackbaudInterface.
 *
 * @package Drupal\blackbaud_sky_api
 */
interface BlackbaudInterface {

  /**
   * The redirect url for Blackbaud.
   */
  const BLACKBAUD_SKY_API_REDIRECT_URI = 'blackbaud/oauth';

  /**
   * The oauth url for Blackbaud.
   */
  const BLACKBAUD_SKY_API_OAUTH_URL = 'https://oauth2.sky.blackbaud.com';

  /**
   * The api url for Blackbaud.
   */
  const BLACKBAUD_SKY_API_URL = 'https://api.sky.blackbaud.com';

}
