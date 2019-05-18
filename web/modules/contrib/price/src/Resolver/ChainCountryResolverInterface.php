<?php

namespace Drupal\price\Resolver;

/**
 * Runs the added resolvers one by one until one of them returns the country.
 *
 * Each resolver in the chain can be another chain, which is why this interface
 * extends the country resolver one.
 */
interface ChainCountryResolverInterface extends CountryResolverInterface {

  /**
   * Adds a resolver.
   *
   * @param \Drupal\price\Resolver\CountryResolverInterface $resolver
   *   The resolver.
   */
  public function addResolver(CountryResolverInterface $resolver);

  /**
   * Gets all added resolvers.
   *
   * @return \Drupal\price\Resolver\CountryResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers();

}
