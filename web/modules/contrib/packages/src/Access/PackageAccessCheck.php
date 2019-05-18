<?php

namespace Drupal\packages\Access;

use Drupal\packages\PackagesInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Class PackageAccessCheck.
 *
 * Determines access to routes based on the state of a given package for the
 * current user.
 *
 * @package Drupal\packages\Access
 */
class PackageAccessCheck implements AccessInterface {

  /**
   * The packages service.
   *
   * @var \Drupal\packages\PackagesInterface
   */
  protected $packages;

  /**
   * Constructor.
   *
   * @param \Drupal\packages\PackagesInterface $packages
   *   The packages service.
   */
  public function __construct(PackagesInterface $packages) {
    $this->packages = $packages;
  }

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $account) {
    // Extract the package.
    $package_id = $route->getRequirement('_package');

    // Skip access check if no package is provided.
    if ($package_id === NULL) {
      return AccessResult::neutral();
    }

    // Load the state of this package.
    $state = $this->packages->getState($package_id);

    // Check if the package is active.
    if ($state->isActive()) {
      $result = AccessResult::allowed();
    }
    else {
      $result = AccessResult::forbidden();
    }

    return $result->cachePerUser();
  }

}
