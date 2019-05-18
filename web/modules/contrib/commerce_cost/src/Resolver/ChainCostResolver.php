<?php

namespace Drupal\commerce_cost\Resolver;

use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;

/**
 * Default implementation of the chain cost resolver.
 */
class ChainCostResolver implements ChainCostResolverInterface {

  /**
   * The resolvers.
   *
   * @var \Drupal\commerce_cost\Resolver\CostResolverInterface[]
   */
  protected $resolvers = [];

  /**
   * Constructs a new ChainBaseCostResolver object.
   *
   * @param \Drupal\commerce_cost\Resolver\CostResolverInterface[] $resolvers
   *   The resolvers.
   */
  public function __construct(array $resolvers = []) {
    $this->resolvers = $resolvers;
  }

  /**
   * {@inheritdoc}
   */
  public function addResolver(CostResolverInterface $resolver) {
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
  public function resolve(PurchasableEntityInterface $entity, $quantity, Context $context) {
    foreach ($this->resolvers as $resolver) {
      $result = $resolver->resolve($entity, $quantity, $context);
      if ($result) {
        return $result;
      }
    }
  }

}
