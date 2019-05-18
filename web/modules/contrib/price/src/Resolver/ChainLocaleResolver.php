<?php

namespace Drupal\price\Resolver;

/**
 * Default implementation of the chain locale resolver.
 */
class ChainLocaleResolver implements ChainLocaleResolverInterface {

  /**
   * The resolvers.
   *
   * @var \Drupal\price\Resolver\LocaleResolverInterface[]
   */
  protected $resolvers = [];

  /**
   * Constructs a new ChainLocaleResolver object.
   *
   * @param \Drupal\price\Resolver\LocaleResolverInterface[] $resolvers
   *   The resolvers.
   */
  public function __construct(array $resolvers = []) {
    $this->resolvers = $resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function addResolver(LocaleResolverInterface $resolver) {
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
  public function resolve() {
    foreach ($this->resolvers as $resolver) {
      $result = $resolver->resolve();
      if ($result) {
        return $result;
      }
    }
  }

}
