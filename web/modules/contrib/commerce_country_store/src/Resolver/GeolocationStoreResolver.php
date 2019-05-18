<?php

namespace Drupal\commerce_country_store\Resolver;

use Drupal\commerce_store\Entity\Store;
use Drupal\commerce_store\Resolver\StoreResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\geoip\GeoLocation;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns the default store, if known.
 */
class GeolocationStoreResolver implements StoreResolverInterface {

  /**
   * The store storage.
   *
   * @var \Drupal\commerce_store\StoreStorageInterface
   */
  protected $storage;

  /**
   * The current request
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * @var \Drupal\geoip\GeoLocation
   */
  protected $geoLocation;

  /**
   * Constructs a new DefaultStoreResolver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $requestStack, GeoLocation $geoLocation) {
    $this->storage = $entity_type_manager->getStorage('commerce_store');
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->geoLocation = $geoLocation;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {

    if ($countryCode = $this->getCountryCode()) {
      foreach ($this->storage->loadMultiple() as $store) {
        /** @var Store $store */
        foreach ($store->getBillingCountries() as $availableCountry) {
          // Return the first store servicing the given country code.
          if ($availableCountry == $countryCode) {
            return $store;
          }
        }
      }
    }
  }

  /**
   * Get the country code for the current request.
   *
   * @return string|null
   */
  public function getCountryCode() {

    $ip = $this->getCurrentIP();

    if ($ip && $location = $this->geoLocation->geolocate($ip)) {
      return $location;
    }
  }

  /**
   * Get the IP from the current request.
   *
   * @return string|null
   */
  protected function getCurrentIP() {
    // Get the client ip as passed on by varnish.
    $ip = $this->currentRequest->headers->get('x-real-ip');

    if (!$ip) {
      // Get the client ip directly.
      $ip = $this->currentRequest->getClientIp();
    }

    // Overrides for testing purpose.
    // A German IP6 address.
    // $ip = '2003:8b:2f1d:c900:30b0:dfb9:ef69:d9f8';
    // A british IP4 address.
    // $ip = '138.68.141.248';
    // A finnish IP address
    // $ip = '94.237.33.88';
    // A French IP address
    // $ip = '46.165.64.0';

    return $ip;
  }

}
