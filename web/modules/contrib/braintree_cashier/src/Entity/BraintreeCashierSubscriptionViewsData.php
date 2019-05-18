<?php

namespace Drupal\braintree_cashier\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Subscription entities.
 */
class BraintreeCashierSubscriptionViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    $data['braintree_cashier_subscription']['subscription_type'] = [
      'title' => t('Subscription Type'),
      'help' => t('The subscription type.'),
      'field' => [
        'id' => 'field',
        'options callback' => 'braintree_cashier_get_subscription_type_options',
        'field_name' => 'subscription_type',
      ],
      'filter' => [
        'id' => 'in_operator',
        'options callback' => 'braintree_cashier_get_subscription_type_options',
      ],
    ];

    return $data;
  }

}
