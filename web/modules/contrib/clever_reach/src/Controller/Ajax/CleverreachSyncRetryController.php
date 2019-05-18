<?php

namespace Drupal\clever_reach\Controller\Ajax;

use CleverReach\BusinessLogic\Sync\InitialSyncTask;
use CleverReach\Infrastructure\Logger\Logger;
use Drupal\clever_reach\Component\Utility\TaskQueue;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Retry Sync endpoint.
 */
class CleverreachSyncRetryController {

  /**
   * Return an array to be run through json_encode and sent to the client.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON string.
   */
  public function post(Request $request) {
    if (!$request->isMethod('POST')) {
      return new JsonResponse(['status' => 'failed']);
    }

    try {
      TaskQueue::enqueue(new InitialSyncTask(), TRUE);
    }
    catch (\Exception $e) {
      Logger::logError("Error restarting sync: {$e->getMessage()}");
      return new JsonResponse(['status' => 'failed']);
    }

    return new JsonResponse(['status' => 'success']);
  }

}
