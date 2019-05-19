<?php

namespace Drupal\social_hub\Utils;

/**
 * Implements a chain resolver to get all installed/defined libraries.
 */
class ChainedLibrariesResolver implements ChainedLibrariesResolverInterface {

  /**
   * The collected resolvers.
   *
   * @var \Drupal\social_hub\Utils\LibrariesResolverInterface[]
   */
  protected $resolvers;

  /**
   * {@inheritdoc}
   */
  public function addResolver(LibrariesResolverInterface $resolver) {
    $this->resolvers[] = $resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function getResolvers() {
    return $this->resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(array $args = NULL) {
    $result = [];

    foreach ($this->resolvers as $resolver) {
      $result += $resolver->resolve($args);
    }

    return $result;
  }

}
