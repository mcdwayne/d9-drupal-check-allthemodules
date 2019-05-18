<?php

namespace Drupal\clever_reach\Controller;

use CleverReach\Infrastructure\Logger\Logger;
use Drupal\clever_reach\Component\Repository\ProcessRepository;
use Drupal\clever_reach\Exception\AsyncProcessStartException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Async process starter.
 */
class CleverreachProcessController {

  /**
   * Callback for the cleverreach.cleverreach.process route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response
   *
   * @throws AsyncProcessStartException
   */
  public function start(Request $request) {
    if (!$request->isMethod('POST')) {
      throw new AsyncProcessStartException(
          'Method is not allowed for async process starter.'
      );
    }

    $guid = $request->get('guid');
    $processRepository = new ProcessRepository();

    try {
      $runner = $processRepository->getRunner($guid);
      $runner->run();
    }
    catch (\Exception $e) {
      Logger::logError($e->getMessage(), 'Integration');
    }

    try {
      $processRepository->deleteProcess($guid);
    }
    catch (\Exception $e) {
      Logger::logError($e->getMessage(), 'Integration');
    }

    return new JsonResponse([]);
  }

}
