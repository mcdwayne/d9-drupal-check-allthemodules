<?php

namespace Drupal\commerce_funds;

use Drupal\views\EntityViewsData;

/**
 * Provides improvements to core's generic views integration for entities.
 */
class FundsEntityViewsData extends EntityViewsData {

  /**
   * Alter views data to add our custom handlers.
   *
   * @return data
   *   The data exposed to views.
   */
  public function getViewsData() {
    $data = parent::getViewsData();
    $data['commerce_funds_transactions']['brut_amount']['field']['id'] = 'commerce_funds_amount';
    $data['commerce_funds_transactions']['fee']['field']['id'] = 'commerce_funds_amount';
    $data['commerce_funds_transactions']['net_amount']['field']['id'] = 'commerce_funds_amount';

    return $data;
  }

}
