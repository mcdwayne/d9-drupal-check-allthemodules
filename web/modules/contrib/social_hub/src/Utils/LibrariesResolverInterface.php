<?php

namespace Drupal\social_hub\Utils;

/**
 * Defines an interface for libraries resolvers.
 */
interface LibrariesResolverInterface {

  /**
   * Resolve a list of libraries.
   *
   * @param array|null $args
   *   An array of arguments.
   *
   * @return array
   *   An array of libraries.
   */
  public function resolve(array $args = NULL);

}
