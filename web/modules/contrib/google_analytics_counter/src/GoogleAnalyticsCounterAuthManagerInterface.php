<?php

namespace Drupal\google_analytics_counter;


/**
 * Class GoogleAnalyticsAuthManager.
 *
 * Sets the auth methods.
 *
 * @package Drupal\google_analytics_counter
 */
interface GoogleAnalyticsCounterAuthManagerInterface {

  /**
   * Check to make sure we are authenticated with google.
   *
   * @return bool
   *   True if there is a refresh token set.
   */
  public function isAuthenticated();

  /**
   * Begin authentication to Google authentication page with the client_id.
   */
  public function beginGacAuthentication();

  /**
   * Instantiate a new GoogleAnalyticsCounterFeed object.
   *
   * @return object
   *   GoogleAnalyticsCounterFeed object to authorize access and request data
   *   from the Google Analytics Core Reporting API.
   */
  public function newGaFeed();

  /**
   * Get the list of available web properties.
   *
   * @return array
   *   Array of options.
   */
  public function getWebPropertiesOptions();
}