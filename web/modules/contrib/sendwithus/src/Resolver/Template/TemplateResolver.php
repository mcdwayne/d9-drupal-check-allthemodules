<?php

declare(strict_types = 1);

namespace Drupal\sendwithus\Resolver\Template;

use Drupal\sendwithus\Context;
use Drupal\sendwithus\Template;
use Webmozart\Assert\Assert;

/**
 * Provides a resolver to get template.
 */
class TemplateResolver {

  protected $resolvers;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\sendwithus\Resolver\Template\TemplateResolverInterface[] $resolvers
   *   The resolvers.
   */
  public function __construct(array $resolvers = []) {
    Assert::allIsInstanceOf($resolvers, TemplateResolverInterface::class);
    $this->resolvers = $resolvers;
  }

  /**
   * Gets the resolvers.
   *
   * @return \Drupal\sendwithus\Resolver\Template\TemplateResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers() : array {
    return $this->resolvers;
  }

  /**
   * Adds the resolver.
   *
   * @param \Drupal\sendwithus\Resolver\Template\TemplateResolverInterface $resolver
   *   The resolver to add.
   *
   * @return \Drupal\sendwithus\Resolver\Template\TemplateResolver
   *   The self.
   */
  public function addResolver(TemplateResolverInterface $resolver) : self {
    $this->resolvers[] = $resolver;
    return $this;
  }

  /**
   * Resolves the template for given parameters.
   *
   * @param \Drupal\sendwithus\Context $context
   *   The context.
   *
   * @return \Drupal\sendwithus\Template|null
   *   The template or null.
   */
  public function resolve(Context $context) : ? Template {
    foreach ($this->resolvers as $resolver) {
      if (!$template = $resolver->resolve($context)) {
        continue;
      }
      return $template;
    }
    return NULL;
  }

}
