<?php

namespace Drupal\snipcart;

/**
 * Incoming Snipcart request validation service.
 */
interface SnipcartRequestValidationServiceInterface {


  /**
   * Check whether the request has a valid X-Snipcart-RequestToken.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   the incoming request to validate
   *
   * @return bool
   *   weather the X-Snipcart-RequestToken is valid or not.
   */
  public function validateRequest($request);


}
