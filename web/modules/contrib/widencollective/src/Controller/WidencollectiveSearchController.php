<?php

namespace Drupal\widencollective\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\widencollective\WidencollectiveSearchService;

/**
 * Widencollective controller for the widencollective module.
 */
class WidencollectiveSearchController extends ControllerBase {

  /**
   * Request winden search url.
   *
   * @return json
   *   Returns a JSON feed.
   */
  public function getSearchUrl() {
    $widen_account = \Drupal::service('user.data')
      ->get('widencollective', \Drupal::currentUser()->id(), 'account');

    if (!isset($widen_account['widen_token'])) {
      $widen_account['widen_token'] = FALSE;
    }

    $result = WidencollectiveSearchService::getSearchConnectorUiUrl($widen_account['widen_token']);
    return new JsonResponse($result);
  }

}
