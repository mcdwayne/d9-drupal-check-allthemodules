<?php

namespace Drupal\country_path;

/**
 * Provides methods for VariantCollectionInterface.
 */
trait CountryPathTrait {

  /**
   * Get ActiveDomain from Domain.negotiator service.
   */
  public function getActiveDomain($reset = FALSE) {
    static $active_domain;

    if (!isset($active_domain) || $reset) {
      $domain_negotiator = \Drupal::service('domain.negotiator');
      $active_domain = $domain_negotiator->getActiveDomain($reset);

      // Similar to Domain module's DomainSourcePathProcessor implementation,
      // ensure the kernel event has run to prevent 404's.
      if (empty($active_domain)) {
        $active_domain = $domain_negotiator->getActiveDomain(true);
      }
    }

    return $active_domain;
  }

}
