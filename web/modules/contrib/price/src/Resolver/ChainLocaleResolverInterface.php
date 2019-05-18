<?php

namespace Drupal\price\Resolver;

/**
 * Runs the added resolvers one by one until one of them returns the locale.
 *
 * Each resolver in the chain can be another chain, which is why this interface
 * extends the locale resolver one.
 */
interface ChainLocaleResolverInterface extends LocaleResolverInterface {

  /**
   * Adds a resolver.
   *
   * @param \Drupal\price\Resolver\LocaleResolverInterface $resolver
   *   The resolver.
   */
  public function addResolver(LocaleResolverInterface $resolver);

  /**
   * Gets all added resolvers.
   *
   * @return \Drupal\price\Resolver\LocaleResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers();

}
