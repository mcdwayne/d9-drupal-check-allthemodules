<?php

namespace Drupal\message_thread_history\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\message\MessageInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns responses for message_thread History module routes.
 */
class HistoryController extends ControllerBase {

  /**
   * Marks a message_thread as read by the current user right now.
   *
   * Cloned from \Drupal\message_history\Controller\HistoryController::read().
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param \Drupal\message_thread\MessageInterface $message
   *   The message_thread whose "last read" timestamp should be updated.
   */
  public function read(Request $request, MessageInterface $message) {
    if ($this->currentUser()->isAnonymous()) {
      throw new AccessDeniedHttpException();
    }

    // Update the message_history table,
    // stating that this user viewed this message.
    message_history_write($message->id());
    // Update the message_thread_history table.
    // This assumes when on message is read the whole thread is read.
    message_thread_history_write($message->id());

    return new JsonResponse((int) message_history_read($message->id()));
  }

}
