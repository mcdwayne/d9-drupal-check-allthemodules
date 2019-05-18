<?php

namespace Drupal\commerce_vipps\Resolver;

/**
 * Runs the added resolvers one by one until one of them returns the order id.
 *
 * Each resolver in the chain can be another chain, which is why this interface
 * extends the order id resolver one.
 */
interface ChainOrderIdResolverInterface extends OrderIdResolverInterface {

  /**
   * Adds a resolver.
   *
   * @param \Drupal\commerce_vipps\Resolver\OrderIdResolverInterface $resolver
   *   The resolver.
   */
  public function addResolver(OrderIdResolverInterface $resolver);

  /**
   * Gets all added resolvers.
   *
   * @return \Drupal\commerce_vipps\Resolver\OrderIdResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers();

}
