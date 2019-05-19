<?php

namespace Drupal\unpublished_nodes_redirect\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\unpublished_nodes_redirect\Utils\UnpublishedNodesRedirectUtils as Utils;

/**
 *
 */
class UnpublishedNodesRedirectSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('redirectUnpublishedNodes');
    return $events;
  }

  /**
   * Fires redirects whenever the KernelEvents::RESPONSE event is dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function redirectUnpublishedNodes(FilterResponseEvent $event) {
    // Only act on nodes.
    if ($event->getRequest()->attributes->get('node') != NULL) {
      $node_type = $event->getRequest()->attributes->get('node')->get('type')->getString();
      $node_status = $event->getRequest()->attributes->get('node')->get('status')->getString();
      $config = \Drupal::config('unpublished_nodes_redirect.settings');
      $is_anonymous = \Drupal::currentUser()->isAnonymous();
      // Get the redirect path for this node type.
      $redirect_path = $config->get(Utils::getNodeTypeKey($node_type));
      // Get the response code for this node type.
      $response_code = $config->get(Utils::getResponseCodeKey($node_type));

      if (Utils::checksBeforeRedirect($node_status, $is_anonymous, $redirect_path, $response_code)) {
        $event->setResponse(new RedirectResponse($redirect_path, $response_code));
      }
    }
  }

}
