<?php

/**
 * @file
 * Contains the credit/debit filter handler.
 */

namespace Drupal\balance_tracker\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Handler to filter whether balance item is a credit or debit.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("balance_items_type")
 */
class BalanceItemsType extends InOperator {

  function getValueOptions() {
    if (!isset($this->valueOptions)) {
      $this->valueTitle = t('Type');
      $this->valueOptions = array(
        'debit' => t('Debit'),
        'credit' => t('Credit'),
      );
    }
    return $this->valueOptions;
  }

}
