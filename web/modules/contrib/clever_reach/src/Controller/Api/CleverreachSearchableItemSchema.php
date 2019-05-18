<?php

namespace Drupal\clever_reach\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * V1 Searchable item schema endpoint.
 */
class CleverreachSearchableItemSchema extends CleverreachBaseSearchController {

  /**
   * Gets list of supported searchable item fields from Drupal system.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return string
   *   JSON string.
   */
  public function get(Request $request) {
    try {
      $result = $this->getSchemaProvider()->getSchema($request->get('type'))->toArray();
    }
    catch (\Exception $e) {
      $result = ['status' => 'error', 'message' => $e->getMessage()];
    }

    return new JsonResponse($result);
  }

}
