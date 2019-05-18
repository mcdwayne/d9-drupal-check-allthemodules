<?php

namespace Drupal\commerce_cost\Resolver;

/**
 * Runs the added resolvers one by one until one of them returns the price.
 *
 * Each resolver in the chain can be another chain, which is why this interface
 * extends the base price resolver one.
 */
interface ChainCostResolverInterface extends CostResolverInterface {

  /**
   * Adds a resolver.
   *
   * @param \Drupal\commerce_cost\Resolver\CostResolverInterface $resolver
   *   The resolver.
   */
  public function addResolver(CostResolverInterface $resolver);

  /**
   * Gets all added resolvers.
   *
   * @return \Drupal\commerce_price\Resolver\PriceResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers();

}
