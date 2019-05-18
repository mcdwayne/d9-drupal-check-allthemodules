<?php

namespace Drupal\price\Resolver;

/**
 * Default implementation of the chain country resolver.
 */
class ChainCountryResolver implements ChainCountryResolverInterface {

  /**
   * The resolvers.
   *
   * @var \Drupal\price\Resolver\CountryResolverInterface[]
   */
  protected $resolvers = [];

  /**
   * Constructs a new ChainCountryResolver object.
   *
   * @param \Drupal\price\Resolver\CountryResolverInterface[] $resolvers
   *   The resolvers.
   */
  public function __construct(array $resolvers = []) {
    $this->resolvers = $resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function addResolver(CountryResolverInterface $resolver) {
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
