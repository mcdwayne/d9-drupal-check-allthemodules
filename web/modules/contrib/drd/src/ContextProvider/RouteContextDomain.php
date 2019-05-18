<?php

namespace Drupal\drd\ContextProvider;

/**
 * Sets the current domain as a context on domain routes.
 */
class RouteContextDomain extends RouteContext {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'drd_domain';
  }

}
