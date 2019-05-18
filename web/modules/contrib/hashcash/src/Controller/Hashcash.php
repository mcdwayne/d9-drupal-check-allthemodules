<?php

/**
 * @file
 * Contains \Drupal\hashcash\Controller\Hashcash.
 */

namespace Drupal\hashcash\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for promotion_queue routes.
 */
class Hashcash extends ControllerBase {
  /**
   * Get a hash cash
   */
  public function get($form_id) {
    global $cookie_domain;
    $result = '1:' . date('ymd') . ':' . $form_id . ':' . \Drupal::request()->getClientIp() . ':' . $cookie_domain . ':';
    return new JsonResponse($result);
  }
}
