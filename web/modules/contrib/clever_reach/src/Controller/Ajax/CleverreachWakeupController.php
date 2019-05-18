<?php

namespace Drupal\clever_reach\Controller\Ajax;

use Drupal\clever_reach\Component\Utility\TaskQueue;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Wakeup endpoint.
 */
class CleverreachWakeupController {

  /**
   * Return an array to be run through json_encode and sent to the client.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON string.
   */
  public function render() {
    TaskQueue::wakeup();
    return new JsonResponse(['status' => 'success']);
  }

}
