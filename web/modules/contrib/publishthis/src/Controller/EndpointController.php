<?php

/**
 * Contains \Drupal\publishthis\Controller\EndpointController.
 */

namespace Drupal\publishthis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\publishthis\Classes\Publishthis_Endpoint;

class EndpointController extends ControllerBase {

  public function processRequest(Request $request) {
    $config = $this->config('publishthis.settings');
    $validEndpoint = $config->get('pt_endpoint');
    $endpoint = \Drupal::request()->query->get('q');
    
    if ($endpoint == $validEndpoint ) {
      $objEndpoint = new Publishthis_Endpoint();
      $result = $objEndpoint->process_request();
      return new JsonResponse($result);
    }
    else {
      throw new AccessDeniedHttpException();
    }
    
  }
}
