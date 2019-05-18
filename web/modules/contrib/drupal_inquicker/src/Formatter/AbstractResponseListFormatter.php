<?php

namespace Drupal\drupal_inquicker\Formatter;

use Drupal\drupal_inquicker\Inquicker\RowCollection;

/**
 * Formats a RowCollection as an array of ids.
 */
abstract class AbstractResponseListFormatter extends Formatter {

  /**
   * {@inheritdoc}
   */
  public function catchError(\Throwable $t) {
    $this->watchdogThrowable($t);
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function formatValidatedSource($data) {
    $return = [];
    foreach ($data as $item) {
      $return[$item->id()] = $this->formatIndividualItem($item);
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSource($data) {
    $this->validateClass($data, RowCollection::class);
    $data->validateMembers();
  }

}
