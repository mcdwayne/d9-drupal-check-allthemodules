<?php

namespace Drupal\drd\ContextProvider;

/**
 * Sets the current core as a context on core routes.
 */
class RouteContextCore extends RouteContext {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'drd_core';
  }

}
