<?php

declare(strict_types = 1);

namespace Drupal\commerce_klarna_payments;

use Drupal\commerce_order\Entity\OrderInterface;
use Webmozart\Assert\Assert;

/**
 * Attempts to resolve RFC 1766 locale for given customer.
 */
class LocaleResolver implements LocaleResolverInterface {

  protected $resolvers;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\commerce_klarna_payments\LocaleResolverInterface[] $collectors
   *   The resolvers.
   */
  public function __construct(array $collectors = []) {
    Assert::allIsInstanceOf($collectors, LocaleResolverInterface::class);
    $this->resolvers = $collectors;
  }

  /**
   * Gets the collectors.
   */
  public function getResolvers() : array {
    return $this->resolvers;
  }

  /**
   * Adds the resolver.
   *
   * @param \Drupal\commerce_klarna_payments\LocaleResolverInterface $resolver
   *   The resolver.
   *
   * @return $this
   *   The self.
   */
  public function addCollector(LocaleResolverInterface $resolver) : self {
    $this->resolvers[] = $resolver;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(OrderInterface $order) : string {
    foreach ($this->resolvers as $collector) {
      if ($value = $collector->resolve($order)) {
        return $value;
      }
    }
    throw new \LogicException('Failed to resolve locale.');
  }

}
