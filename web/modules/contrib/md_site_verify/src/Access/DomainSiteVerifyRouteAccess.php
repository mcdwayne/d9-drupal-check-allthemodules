<?php

namespace Drupal\md_site_verify\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\domain\DomainNegotiatorInterface;

/**
 * Determines access to routes based on domains.
 */
class DomainSiteVerifyRouteAccess implements AccessInterface {

  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $domainNegotiator;

  /**
   * Constructs the object.
   *
   * @param DomainNegotiatorInterface $negotiator
   *   The domain negotiation service.
   */
  public function __construct(DomainNegotiatorInterface $negotiator) {
    $this->domainNegotiator = $negotiator;
  }

  /**
   * Checks access to a route domain.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {
    $domain = $this->domainNegotiator->getActiveDomain();
    $domainAllowed = \Drupal::service('md_site_verify_service')
      ->domainSiteVerifyAccessCheck($domain->id());
    if ($domainAllowed) {
      return AccessResult::allowed()->addCacheContexts(['url.site']);
    }
    // If there is no allowed domain, give other access checks a chance.
    return AccessResult::neutral()->addCacheContexts(['url.site']);
  }

}
