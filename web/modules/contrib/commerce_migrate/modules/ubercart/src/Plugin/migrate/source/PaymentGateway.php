<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Ubercart payment gateway source.
 *
 * Migrate the Drupal 6 payment methods to a manual payment gateway.
 *
 * @MigrateSource(
 *   id = "uc_payment_gateway",
 *   source_module = "uc_payment"
 * )
 */
class PaymentGateway extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('uc_payment_receipts', 'upr')
      ->distinct()
      ->fields('upr', ['method']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'method' => $this->t('Payment method'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'method' => [
        'type' => 'string',
        'alias' => 'upr',
      ],
    ];
  }

}
