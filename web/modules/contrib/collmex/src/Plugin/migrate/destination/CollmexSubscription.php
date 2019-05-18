<?php

namespace Drupal\collmex\Plugin\migrate\destination;

use Drupal\collmex\CsvBuilder\ImportSubscriptionCsvBuilder;
use Drupal\migrate\Row;

/**
 * Class CollmexSubscription
 *
 * @MigrateDestination(
 *   id = "collmex_subscription",
 * )
 *
 * @package Drupal\collmex\Plugin\migrate\destination
 */
class CollmexSubscription extends CollmexBase {

  protected function getCsvBuilder() {
    return new ImportSubscriptionCsvBuilder();
  }

  // @todo This will break for more than one subscription per member.
  public function import(Row $row, array $oldDestinationIdValues = []) {
    // Fix next_invoice date for new entries.
    if (!array_filter($oldDestinationIdValues)) {
      $row->setDestinationProperty('next_invoice', $row->getDestinationProperty('valid_from'));
    }
    return parent::import($row, $oldDestinationIdValues);
  }

}
