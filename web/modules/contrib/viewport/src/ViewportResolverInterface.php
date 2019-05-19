<?php

namespace Drupal\viewport;

/**
 * Defines an interface for viewport tag resolvers.
 */
interface ViewportResolverInterface {

  /**
   * Checks if the given path (or current path) needs a custom viewport tag.
   *
   * @return bool
   *   Whether the given path needs to use a custom viewport or not.
   */
  public function isPathSelected($path = NULL);

  /**
   * Generates and returns an html_head tag array for use as page #attachment.
   *
   * @return array
   *   Array specifying the html_head '#tag' and '#attributes' properties.
   */
  public function generateViewportTagArray();

}
