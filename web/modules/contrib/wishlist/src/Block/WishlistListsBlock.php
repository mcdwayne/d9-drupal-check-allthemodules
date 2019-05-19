<?php

/**
 * @file
 * Contains \Drupal\wishlist\Plugin\Block\WishlistListsBlock.
 */

namespace Drupal\wishlist\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Wishlist lists block.
 *
 * @Block(
 *   id = "wishlist_lists",
 *   admin_label = @Translation("Wishlists")
 * )
 */
class WishlistListsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // @todo use database from service
    $query = db_select('node', 'n');
    $query->join('users', 'u', 'n.uid = u.uid');
    $query->condition('n.type', 'wishlist')
      ->addTag('node_access')
      ->fields('n', array('nid', 'title', 'uid'))
      ->fields('u', array('name'))
      ->groupBy('u.uid')
      ->orderBy('u.name', 'DESC');
    $result = $query->execute();

    $build = [];
    if ($result->rowCount() > 0) {
      $items = array();
      while ($record = $result->fetchObject()) {
        $items[] = format_wishlists($record);
      }
      // @todo url
      $link = l(t("More"), "wishlist", array('attributes' => array("title" => t("View all wishlists."))));
      // @todo render array 'after'
      $build = [
        '#theme' => 'item_list',
        '#title' => $this->t('Wishlists'),
        '#items' => $items,
        'after'=> $this->t('<div class="more-link">@link</div>', [
          '@link' => $link,
        ]),
      ];
    }
    return $build;
  }

}
