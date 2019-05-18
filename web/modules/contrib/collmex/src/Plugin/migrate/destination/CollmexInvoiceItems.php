<?php

namespace Drupal\collmex\Plugin\migrate\destination;

use Drupal\collmex\CsvBuilder\ImportInvoiceCsvBuilder;

/**
 * Class CollmexInvoice
 *
 * @MigrateDestination(
 *   id = "collmex_invoice_items",
 * )
 *
 * @package Drupal\collmex\Plugin\migrate\destination
 */
class CollmexInvoiceItems extends CollmexMultipleItemsBase {

  protected function getCsvBuilder() {
    return new ImportInvoiceCsvBuilder();
  }

}
