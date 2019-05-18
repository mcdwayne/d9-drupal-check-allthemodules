<?php

namespace Drupal\cleverreach\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * V1 Searchable items endpoint.
 */
class CleverreachSearchableItemsController extends CleverreachBaseSearchController {

  /**
   * Gets list of supported searchable items from CleverReach system. All content types defined in
   * Drupal are currently supported.
   *
   * @return string JSON string.
   */
  public function get() {
    try {
      $result = $this->getSchemaProvider()->getSearchableItems()->toArray();
    }
    catch (\Exception $e) {
      $result = ['status' => 'error', 'message' => $e->getMessage()];
    }

    return new JsonResponse($result);
  }

}
