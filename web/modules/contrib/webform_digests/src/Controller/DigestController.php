<?php

namespace Drupal\webform_digests\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for sending webform digests.
 */
class DigestController {

  /**
   * Send the digests for the day.
   */
  public function sendAction() {
    $queue_count = \Drupal::service('webform_digests.queue_builder')->queueSubmissions();
    return new JsonResponse([
      'queued' => $queue_count,
    ]);
  }

}
