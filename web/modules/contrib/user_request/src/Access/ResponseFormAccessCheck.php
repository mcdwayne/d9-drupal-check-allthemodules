<?php

namespace Drupal\user_request\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks the access to some request's response form.
 */
class ResponseFormAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // Gets the request from the route.
    $request = $route_match->getParameter('user_request');
    $request_type = $request->getRequestType();

    // Checks if the user is in the request's list of recipients.
    $is_recipient = FALSE;
    $recipients = $request->getRecipients();
    foreach ($recipients as $recipient) {
      if ($recipient->id() == $account->id()) {
        $is_recipient = TRUE;
        break;
      }
    }

    // Checks if there is some response transition allowed for current state.
    $has_transition = FALSE;
    $allowed_transitions = $request->getState()->getTransitions();
    $response_transitions = $request_type->getResponseTransitions();
    foreach ($response_transitions as $transition_id) {
      if (!empty($allowed_transitions[$transition_id])) {
        $has_transition = TRUE;
        break;
      }
    }

    // The user must be a recipient a the request must not have been responded.
    // Permission checking is performed by core.
    return AccessResult::allowedIf($is_recipient && $has_transition);
  }

}
