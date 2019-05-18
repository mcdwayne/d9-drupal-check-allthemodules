<?php

namespace Drupal\collmex\Plugin\migrate\destination;

use Drupal\collmex\CsvBuilder\ImportInvoiceCsvBuilder;

/**
 * Class CollmexInvoice
 *
 * @MigrateDestination(
 *   id = "collmex_invoice",
 * )
 *
 * @package Drupal\collmex\Plugin\migrate\destination
 */
class CollmexInvoice extends CollmexBase {

  const TYPE_INVOICE = 0;
  const TYPE_CREDIT = 1;
  const TYPE_PRELIMINARY_INVOICE = 2;
  const TYPE_CACHSALE = 3;
  const TYPE_RETOUR_CREDIT = 4;
  const TYPE_PROFORMA_INVOICE = 5;

  const PAYMENT_NOW = 1;
  const PAYMENT_NONE = 10;
  const PAYMENT_DIRECT_DEBIT = 5;

  public static function invoiceType($amount) {
    return $amount < 0 ? self::TYPE_CREDIT : self::TYPE_INVOICE;
  }

  public static function termsOfPayment($amount) {
    return $amount < 0 ? self::PAYMENT_NOW : self::PAYMENT_DIRECT_DEBIT;
  }

  protected function getCsvBuilder() {
    return new ImportInvoiceCsvBuilder();
  }

}
