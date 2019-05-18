<?php

namespace Drupal\drd\ContextProvider;

/**
 * Interface for RouteContext.
 */
interface RouteContextInterface {

  /**
   * Get the type of the DRD entity.
   *
   * @return string
   *   DRD entity type.
   */
  public function getType();

}
