<?php

/**
 * @file
 * Page callbacks for module mpac.
 */

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Page callback: Outputs JSON for autocomplete suggestions.
 *
 * @param $type
 *   The type of data to return.
 */
function mpac_autocomplete($type) {
  $matches = array();

  return new JsonResponse($matches);
}
