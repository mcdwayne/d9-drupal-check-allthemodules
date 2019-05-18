<?php

namespace Drupal\commerce_wishlist_api\Access;

use Drupal\commerce_wishlist\Entity\WishlistInterface;
use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Drupal\commerce_wishlist\WishlistProviderInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Access check for the wishlist rest plugins.
 *
 * Uses the wishlist provider to check if a wishlist belongs to the current
 * session, and also verifies wishlist items belong to a valid wishlist.
 */
class WishlistApiAccessCheck implements AccessInterface {

  /**
   * The wishlist provider.
   *
   * @var \Drupal\commerce_wishlist\WishlistProviderInterface
   */
  protected $wishlistProvider;

  /**
   * Constructs a WishlistApiAccessCheck object.
   *
   * @param \Drupal\commerce_wishlist\WishlistProviderInterface $wishlist_provider
   *   The wishlist provider.
   */
  public function __construct(WishlistProviderInterface $wishlist_provider) {
    $this->wishlistProvider = $wishlist_provider;
  }

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // If the route has no parameters (wishlist collection), allow.
    $parameters = $route->getOption('parameters');
    if (empty($parameters['commerce_wishlist'])) {
      return AccessResult::allowed();
    }

    // If there is no wishlist, no access.
    $wishlist = $route_match->getParameter('commerce_wishlist');
    if (!$wishlist || !$wishlist instanceof WishlistInterface) {
      return AccessResult::forbidden();
    }

    // Ensure wishlist belongs to the current user.
    $wishlists = $this->wishlistProvider->getWishlistIds($account);
    if (!in_array($wishlist->id(), $wishlists)) {
      return AccessResult::forbidden()->addCacheableDependency($wishlist);
    }

    // If there is also an wishlist item in the route, make sure it belongs
    // to this wishlist as well.
    $wishlist_item = $route_match->getParameter('commerce_wishlist_item');
    if ($wishlist_item && $wishlist_item instanceof WishlistItemInterface) {
      if (!$wishlist->hasItem($wishlist_item)) {
        return AccessResult::forbidden()
          ->addCacheableDependency($wishlist_item)
          ->addCacheableDependency($wishlist);
      }
    }

    return AccessResult::allowed()->addCacheableDependency($wishlist);
  }

}
