<?php

/**
 * @file
 * Contains \Drupal\time_spent\Access\timespentTokenAccessCheck.
 */

namespace Drupal\time_spent\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\CsrfTokenGenerator;

/**
 * Determines access to routes based on login status of current user.
 */
class timespentTokenAccessCheck implements AccessInterface {
  /**
   * Constructs a timespentTokenAccessCheck object.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   */
  function __construct(CsrfTokenGenerator $csrf_token) {
    $this->csrfToken = $csrf_token;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    return $this->csrfToken->validate($request->query->get('token')) ? AccessResult::allowed() : AccessResult::forbidden();
  }
}

