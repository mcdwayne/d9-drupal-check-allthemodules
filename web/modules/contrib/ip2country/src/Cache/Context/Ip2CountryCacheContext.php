<?php

namespace Drupal\ip2country\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\RequestStackCacheContextBase;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\ip2country\Ip2CountryManagerInterface;

/**
 * Defines the Ip2CountryCacheContext service, for "per country" caching.
 *
 * Cache context ID: 'ip.country'.
 */
class Ip2CountryCacheContext extends RequestStackCacheContextBase implements CacheContextInterface {

  /**
   * The ip2country.manager service.
   *
   * @var \Drupal\ip2country\Ip2CountryManagerInterface
   */
  protected $ip2countryManager;

  /**
   * Constructs an Ip2CountryCacheContext.
   *
   * @param \Drupal\ip2country\Ip2CountryManagerInterface $ip2countryManager
   *   The ip2country.manager service.
   */
  public function __construct(Ip2CountryManagerInterface $ip2countryManager) {
    $this->ip2countryManager = $ip2countryManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('County based on IP address');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->ip2countryManager->getCountry($this->requestStack->getCurrentRequest()->getClientIp());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
