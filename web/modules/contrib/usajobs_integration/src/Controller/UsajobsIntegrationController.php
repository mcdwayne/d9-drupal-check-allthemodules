<?php

namespace Drupal\usajobs_integration\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for usajobs routes.
 */
class UsajobsIntegrationController extends ControllerBase {

  /**
   * List filtered jobs from the USAJobs Search API.
   *
   * @return object
   *   Return a response object.
   */
  public function listings() {

    $requestData = \Drupal::service('usajobs_integration.request.data');
    return $requestData->getResponse();

  }

}
