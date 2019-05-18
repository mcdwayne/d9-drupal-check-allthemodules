<?php

namespace Drupal\item_lot;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the item_lot entity types.
 */
class ItemLotViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['item_lot']['item']['argument']['id'] = 'item';
    // https://www.drupal.org/node/2489476
    // $data['item_lot']['expiration_date']['filter']['id'] = 'datetime';

    return $data;
  }

}
