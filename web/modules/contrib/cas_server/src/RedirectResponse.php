<?php

/**
 * @file
 * Contains Drupal\cas_server\RedirectResponse.
 */

namespace Drupal\cas_server;

use Drupal\Component\HttpFoundation\SecuredRedirectResponse;

/**
 * Provides an external redirect that is not cacheable by Drupal.
 */
class RedirectResponse extends SecuredRedirectResponse {

  /**
   * {@inheritdoc}
   */
  protected function isSafe($url) {
    return TRUE;
  }
}
