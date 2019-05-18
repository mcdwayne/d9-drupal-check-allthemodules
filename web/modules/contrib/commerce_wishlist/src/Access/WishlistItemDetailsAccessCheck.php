<?php

namespace Drupal\commerce_wishlist\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access check for the wishlist item details_form route.
 */
class WishlistItemDetailsAccessCheck {

  /**
   * Checks access to the wishlist item details form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(RouteMatchInterface $route_match, AccountInterface $account) {
    if ($account->hasPermission('administer commerce_wishlist')) {
      // Administrators can modify anyone's wishlst.
      $access = AccessResult::allowed()->cachePerPermissions();
    }
    else {
      // Users can modify own wishlists.
      /** @var \Drupal\commerce_wishlist\Entity\WishlistItemInterface $wishlist_item */
      $wishlist_item = $route_match->getParameter('commerce_wishlist_item');
      $user = $wishlist_item->getWishlist()->getOwner();
      $access = AccessResult::allowedIf($account->isAuthenticated())
        ->andIf(AccessResult::allowedIf($user->id() == $account->id()))
        ->addCacheableDependency($wishlist_item)
        ->cachePerUser();
    }

    return $access;
  }

}
