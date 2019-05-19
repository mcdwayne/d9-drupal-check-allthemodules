<?php

/**
 * @file
 * Contains \Drupal\wishlist\Entity\WishlistPurchased.
 */

namespace Drupal\wishlist\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\wishlist\WishlistPurchasedInterface;

/**
 * Defines the Wishlist purchased entity.
 *
 * @ConfigEntityType(
 *   id = "wishlist_purchased",
 *   label = @Translation("Wishlist Purchased"),
 *   handlers = {
 *     "list_builder" = "Drupal\wishlist\WishlistPurchasedListBuilder",
 *     "form" = {
 *       "add" = "Drupal\wishlist\Form\WishlistPurchasedForm",
 *       "edit" = "Drupal\wishlist\Form\WishlistPurchasedForm",
 *       "delete" = "Drupal\wishlist\Form\WishlistPurchasedDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\wishlist\WishlistPurchasedHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "wishlist_purchased",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/wishlist_purchased/{wishlist_purchased}",
 *     "add-form" = "/admin/structure/wishlist_purchased/add",
 *     "edit-form" = "/admin/structure/wishlist_purchased/{wishlist_purchased}/edit",
 *     "delete-form" = "/admin/structure/wishlist_purchased/{wishlist_purchased}/delete",
 *     "collection" = "/admin/structure/wishlist_purchased"
 *   }
 * )
 */
class WishlistPurchased extends ConfigEntityBase implements WishlistPurchasedInterface {
  /**
   * The Wishlist purchased ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Wishlist purchased label.
   *
   * @var string
   */
  protected $label;

  /**
   * Wishlist purchase nid.
   *
   * @var string
   */
  protected $nid;

  /**
   * UID of the user that purchased the item.
   *
   * @var string
   */
  protected $uid;

  /**
   * How many this user purchased.
   *
   * @var string
   */
  protected $quantity;

  /**
   * The date of the purchase.
   *
   * @var string
   */
  protected $date;

}