<?php

namespace Drupal\collmex\Plugin\migrate\destination;

use Drupal\collmex\CollmexMessenger;
use Drupal\collmex\CsvBuilder\ImportMemberCsvBuilder;
use Drupal\collmex\Query\CollmexMemberQuery;
use Drupal\migrate\Row;

/**
 * Class CollmexMember
 *
 * @MigrateDestination(
 *   id = "collmex_member",
 * )
 *
 * @package Drupal\collmex\Plugin\migrate\destination
 */
class CollmexMember extends CollmexBase {

  /**
   * @inheritDoc
   */
  protected function getCsvBuilder() {
    return new ImportMemberCsvBuilder();
  }

  /**
   * @inheritDoc
   */
  public function import(Row $row, array $oldDestinationIdValues = []) {
    if (!array_filter($oldDestinationIdValues) && $row->hasDestinationProperty('email')) {
      // Try to find destination by email.
      $messenger = new CollmexMessenger($this->migration, $row->getSourceIdValues());
      $email = $row->getDestinationProperty('email');
      $records = (new CollmexMemberQuery($messenger))
        ->byEmail($email);
      if ($records) {
        /** @var \MarcusJaschen\Collmex\Type\Member $record */
        $record = reset($records);
        $customer_id = $record->customer_id;
        $oldDestinationIdValues = [$customer_id];
      }
      $messenger->saveMessage(sprintf('Queried email, %s results. "%s" => %s', count($records),  $email, isset($customer_id) ? $customer_id : '--'));
    }
    return parent::import($row, $oldDestinationIdValues);
  }

}
