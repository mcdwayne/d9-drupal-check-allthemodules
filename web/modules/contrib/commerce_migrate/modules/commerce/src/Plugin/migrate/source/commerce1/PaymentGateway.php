<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Gets Commerce 1 payment gateway from database.
 *
 * @MigrateSource(
 *   id = "commerce1_payment_gateway",
 *   source_module = "commerce_payment"
 * )
 */
class PaymentGateway extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('commerce_payment_transaction', 'cpt')
      ->distinct()
      ->fields('cpt', ['payment_method']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'payment_method' => $this->t('Payment method'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'payment_method' => [
        'type' => 'string',
      ],
    ];
  }

}
