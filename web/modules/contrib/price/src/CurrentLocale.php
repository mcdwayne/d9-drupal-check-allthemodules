<?php

namespace Drupal\price;

use Drupal\price\Resolver\ChainLocaleResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Holds a reference to the current locale, resolved on demand.
 *
 * The ChainLocaleResolver runs the registered locale resolvers one by one until
 * one of them returns the locale.
 * The DefaultLocaleResolver runs last, and contains the default logic
 * which assembles the locale based on the current language and country.
 *
 * @see \Drupal\price\Resolver\ChainLocaleResolver
 * @see \Drupal\price\Resolver\DefaultLocaleResolver
 */
class CurrentLocale implements CurrentLocaleInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The chain resolver.
   *
   * @var \Drupal\price\Resolver\ChainLocaleResolverInterface
   */
  protected $chainResolver;

  /**
   * Static cache of resolved locales. One per request.
   *
   * @var \SplObjectStorage
   */
  protected $locales;

  /**
   * Constructs a new CurrentLocale object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\price\Resolver\ChainLocaleResolverInterface $chain_resolver
   *   The chain resolver.
   */
  public function __construct(RequestStack $request_stack, ChainLocaleResolverInterface $chain_resolver) {
    $this->requestStack = $request_stack;
    $this->chainResolver = $chain_resolver;
    $this->locales = new \SplObjectStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function getLocale() {
    $request = $this->requestStack->getCurrentRequest();
    if (!$this->locales->contains($request)) {
      $this->locales[$request] = $this->chainResolver->resolve();
    }

    return $this->locales[$request];
  }

}
