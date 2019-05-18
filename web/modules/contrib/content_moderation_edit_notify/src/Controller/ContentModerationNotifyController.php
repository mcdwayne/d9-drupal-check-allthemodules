<?php

namespace Drupal\content_moderation_edit_notify\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Returns responses for Moderation Notify module routes.
 */
class ContentModerationNotifyController extends ControllerBase {

  /**
   * Check if a new revision exist for a current node.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request of the page.
   * @param \Drupal\node\NodeInterface $node
   *   The node that need to be checked.
   * @param int $vid
   *   Current vid to check for.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function checkNewRevision(Request $request, NodeInterface $node, $vid) {
    $repsonse = NULL;

    // Get last revision for this node.
    $moderation_info = \Drupal::service('content_moderation.moderation_information');
    $lastest_revision = $moderation_info->getLatestRevision('node', $node->id());
    $latest_revision_vid = $lastest_revision->getRevisionId();

    // Check if current user is editing the last revision.
    if ($latest_revision_vid != $vid) {
      $repsonse['last_vid'] = $latest_revision_vid;
      // Get current settings to build the message.
      $config = $this->config('content_moderation_edit_notify.settings');

      $token_service = \Drupal::token();

      if ($lastest_revision->isPublished()) {
        $message = $config->get('message_published');
        $type = "error";
      }
      else {
        $message = $config->get('message_unpublished');
        $type = "warning";
      }
      $message = $token_service->replace($message, ['node' => $lastest_revision]);

      // Mimic status messages.
      $status_message = [
        '#markup' => $message,
        '#prefix' => '<div class="messages messages--' . $type . ' notify-' . $latest_revision_vid . '">',
        '#suffix' => '</div>',
      ];
      $repsonse['message'] = \Drupal::service('renderer')->render($status_message);
    }

    return new JsonResponse($repsonse);
  }

}
