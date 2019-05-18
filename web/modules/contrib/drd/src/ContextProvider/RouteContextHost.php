<?php

namespace Drupal\drd\ContextProvider;

/**
 * Sets the current host as a context on host routes.
 */
class RouteContextHost extends RouteContext {

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return 'drd_host';
  }

}
