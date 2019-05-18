<?php

namespace Drupal\country_path\Cache\Context;

use Drupal\Core\Cache\Context\RequestStackCacheContextBase;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\country_path\CountryPathTrait;

/**
 * Defines the CountryCacheContext service, for "per Country" caching.
 *
 * Cache context ID: 'url.country'.
 *
 * (This allows for caching relative Country prefix in URLs.)
 *
 * @see \Symfony\Component\HttpFoundation\Request::getBasePath()
 * @see \Symfony\Component\HttpFoundation\Request::getPathInfo()
 */
class CountryPathCacheContext extends RequestStackCacheContextBase implements CacheContextInterface {

  use CountryPathTrait;

  /**
   * Constructs a new RequestStackCacheContextBase class.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Country');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $active_domain = $this->getActiveDomain();

    // Returns DomainId as context.
    return empty($active_domain) ? '0' : $active_domain->getDomainId();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
