<?php

namespace Drupal\collmex\Plugin\migrate\destination;

use Drupal\collmex\CsvBuilder\ImportProductCsvBuilder;

/**
 * Class CollmexProduct
 *
 * @MigrateDestination(
 *   id = "collmex_product",
 * )
 *
 * @package Drupal\collmex\Plugin\migrate\destination
 */
class CollmexProduct extends CollmexBase {

  protected function getCsvBuilder() {
    return new ImportProductCsvBuilder();
  }

}
