<?php

namespace Drupal\oh_regular\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\oh_regular\OhRegularInterface;
use Symfony\Component\Routing\Route;

/**
 * Determine whether a route is an opening hours bundle.
 */
class IsOhBundleAccessCheck implements AccessInterface {

  /**
   * OH regular service.
   *
   * @var \Drupal\oh_regular\OhRegularInterface
   */
  protected $ohRegular;

  /**
   * Construct OhRegularSubscriber service.
   *
   * @param \Drupal\oh_regular\OhRegularInterface $ohRegular
   *   OH regular service.
   */
  public function __construct(OhRegularInterface $ohRegular) {
    $this->ohRegular = $ohRegular;
  }

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The currently logged in account.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, AccountInterface $currentUser, RouteMatchInterface $routeMatch): AccessResultInterface {
    $parameter = $route->getRequirement('_is_oh_bundle');
    $entity = $routeMatch->getParameter($parameter);

    if (!$entity instanceof EntityInterface) {
      return AccessResult::neutral();
    }

    return $this->ohRegular->hasOpeningHours($entity);
  }

}
