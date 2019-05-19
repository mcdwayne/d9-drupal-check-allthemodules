<?php

/**
 * @file
 * Contains \Drupal\wishlist\Entity\Wishlist.
 */

namespace Drupal\wishlist;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for wishlist entities.
 */
class WishlistViewsData extends EntityViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['wishlist']['wishlist_purchased'] = array(
      'table' => array(
        'field' => 'id',
        'title' => $this->t('Wishlist purchased id'),
        'help' => t('Records of items purchased.'),
      )
    );

    return $data;
  }

}
