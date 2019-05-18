<?php

namespace Drupal\ek_health_check\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class HealthCheckController extends ControllerBase {

  /**
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function index() {
    return new JsonResponse(
      [
        "app" => TRUE,
        "database" => TRUE,
        "version" => \Drupal::VERSION,
        "framework" => "drupal8",
      ]
    );
  }

}
