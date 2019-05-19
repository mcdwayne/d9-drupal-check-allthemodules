<?php

namespace Drupal\social_hub\Utils;

/**
 * Runs libraries resolver services.
 */
interface ChainedLibrariesResolverInterface extends LibrariesResolverInterface {

  /**
   * Adds a resolver.
   *
   * @param \Drupal\social_hub\Utils\LibrariesResolverInterface $resolver
   *   The resolver.
   */
  public function addResolver(LibrariesResolverInterface $resolver);

  /**
   * Gets all added resolvers.
   *
   * @return \Drupal\social_hub\Utils\LibrariesResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers();

}
