<?php

namespace Drupal\message_history\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\message\MessageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Message History module routes.
 */
class HistoryController extends ControllerBase {

  /**
   * Returns a set of messages' last read timestamps.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function getReadTimestamps(Request $request) {
    if ($this->currentUser()->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    $mids = $request->request->get('message_ids');
    if (!isset($mids)) {
      throw new NotFoundHttpException();
    }
    return new JsonResponse(message_history_read_multiple($mids));
  }

  /**
   * Marks a message as read by the current user right now.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param \Drupal\message\MessageInterface $message
   *   The message whose "last read" timestamp should be updated.
   */
  public function read(Request $request, MessageInterface $message) {
    if ($this->currentUser()->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    // Update the message_history table, stating that this user viewed this
    // mesage.
    message_history_write($message->id());

    return new JsonResponse((int) message_history_read($message->id()));
  }

}
