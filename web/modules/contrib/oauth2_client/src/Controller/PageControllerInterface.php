<?php

namespace Drupal\oauth2_client\Controller;

/**
 * Interface for OAuth2 Client page controllers.
 *
 * Methods in this class should return Drupal render arrays.
 */
interface PageControllerInterface {

  /**
   * Defines the client test page for testing OAuth2 Client plugins.
   *
   * @return array
   *   A render array defining the page. Contains the client test form.
   */
  public function clientTestPage();

}
