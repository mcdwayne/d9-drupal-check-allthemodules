<?php

declare(strict_types = 1);

namespace Drupal\sendwithus\Resolver\Variable;

use Drupal\sendwithus\Context;
use Drupal\sendwithus\Template;
use Webmozart\Assert\Assert;

/**
 * Provides a resolver to collect variables.
 */
class VariableCollector {

  protected $collectors;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\sendwithus\Resolver\Variable\VariableCollectorInterface[] $collectors
   *   The resolvers.
   */
  public function __construct(array $collectors = []) {
    Assert::allIsInstanceOf($collectors, VariableCollectorInterface::class);
    $this->collectors = $collectors;
  }

  /**
   * Gets the collectors.
   *
   * @return \Drupal\sendwithus\Resolver\Variable\VariableCollectorInterface[]
   *   The collectors.
   */
  public function getCollectors() : array {
    return $this->collectors;
  }

  /**
   * Adds the resolver.
   *
   * @param \Drupal\sendwithus\Resolver\Variable\VariableCollectorInterface $collector
   *   The resolver to add.
   *
   * @return \Drupal\sendwithus\Resolver\Variable\VariableCollector
   *   The self.
   */
  public function addCollector(VariableCollectorInterface $collector) : self {
    $this->collectors[] = $collector;
    return $this;
  }

  /**
   * Resolves the variables for given parameters.
   *
   * @param \Drupal\sendwithus\Template $template
   *   The template.
   * @param \Drupal\sendwithus\Context $context
   *   The context.
   *
   * @return \Drupal\sendwithus\Template
   *   The template.
   */
  public function collect(Template $template, Context $context) : Template {
    foreach ($this->collectors as $collector) {
      $collector->collect($template, $context);
    }
    return $template;
  }

}
