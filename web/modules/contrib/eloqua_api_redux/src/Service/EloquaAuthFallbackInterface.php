<?php

namespace Drupal\eloqua_api_redux\Service;

/**
 * Interface for elouqa auth fallback using resource owner password grant.
 *
 * @package Drupal\eloqua_api_redux\Service
 */
interface EloquaAuthFallbackInterface {

  /**
   * Calls eloqua authentication API service to generate tokens.
   *
   * Access and refresh tokens are generated using resource owner password
   * credentials grant method.
   *
   * @return bool
   *   TRUE if tokens are generated/renewed from eloqua API.
   */
  public function generateTokensByResourceOwner();

}
