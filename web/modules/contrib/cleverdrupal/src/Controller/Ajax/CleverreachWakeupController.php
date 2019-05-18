<?php

namespace Drupal\cleverreach\Controller\Ajax;

use Drupal\cleverreach\Component\Utility\TaskQueue;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Wakeup endpoint.
 */
class CleverreachWakeupController {

  /**
   * Return an array to be run through json_encode and sent to the client.
   */
  public function render() {
    TaskQueue::wakeup();
    return new JsonResponse(['status' => 'success']);
  }

}
